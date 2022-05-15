<?php

namespace app\components;

use app\components\docker\DockerContainerBuilder;
use app\exceptions\SubmissionRunnerException;
use app\models\InstructorFile;
use app\models\StudentFile;
use app\models\Task;
use Yii;
use yii\helpers\FileHelper;

/**
 * Class to create a running instance of a Docker container populated with student's submission
 */
class SubmissionRunner
{
    private string $workingDirBasePath;
    private StudentFile $studentFile;

    /**
     * Starts a docker container with the student's submission.
     *
     * @param StudentFile $studentFile
     * @param int|null $hostPort optional host port to bind - ignored if not web app. Overrides builder configuration
     * @param string|null $containerName optional container name, if null the container name will be generated
     * @param DockerContainerBuilder|null $builder optinal builder, if null container will be created with Task defaults
     *
     * @return docker\DockerContainer
     *
     * @throws SubmissionRunnerException
     * @throws \yii\base\ErrorException
     * @throws \yii\base\Exception
     */
    public function run(StudentFile $studentFile, ?int $hostPort = null, ?string $containerName = null, ?DockerContainerBuilder $builder = null): docker\DockerContainer
    {
        $this->studentFile = $studentFile;
        $this->initWorkDir();

        $dockerContainer = null;
        try {
            $studentFilesResult = $this->prepareStudentFiles();
            $instructorFilesResult = $this->prepareInstructorFiles();
            $compileInstructionResult = $this->prepareCompileInstructions();
            $runInstructionResult = $this->prepareRunInstructions();

            if ($studentFilesResult && $instructorFilesResult && $compileInstructionResult && $runInstructionResult) {
                $dockerContainer = $this->buildContainer($containerName, $hostPort, $builder);
                $this->tryInitContainer($dockerContainer);
            } else {
                throw new SubmissionRunnerException(
                    'File prepare failed',
                    SubmissionRunnerException::PREPARE_FAILURE
                );
            }
        } finally {
            $this->deleteSolution();
        }
        return $dockerContainer;
    }

    /**
     * Initializes working directory for student and instructor files
     * @return void
     */
    private function initWorkDir()
    {
        $this->workingDirBasePath =
            Yii::$app->basePath
            . '/'
            . Yii::$app->params['data_dir']
            . '/tmp/docker/'
            . Yii::$app->security->generateRandomString(4)
            . '/';
    }

    /**
     * Extracts the student solution to tmp/docker/{studentFileID}/submission/
     *
     * @return bool The success of extraction.
     */
    private function prepareStudentFiles(): bool
    {
        $submissionDir = $this->workingDirBasePath . 'submission/';

        if (!file_exists($submissionDir)) {
            mkdir($submissionDir, 0755, true);
        }

        $zip = new \ZipArchive();
        $res = $zip->open($this->studentFile->path);
        if ($res === true) {
            $zip->extractTo($submissionDir);
            $zip->close();
            return true;
        } else {
            return false;
        }
    }

    /**
     * Copies the instructor defined test files of the task to tmp/docker/{studentFileID}/test_files/
     *
     * @return bool The success of the copy operations.
     */
    private function prepareInstructorFiles(): bool
    {
        $testFileDir = $this->workingDirBasePath . 'test_files/';

        if (!file_exists($testFileDir)) {
            mkdir($testFileDir, 0755, true);
        }

        $testFiles = InstructorFile::find(['taskID' => $this->studentFile->taskID])
            ->onlyTestFiles()
            ->all();

        $success = true;
        foreach ($testFiles as $testFile) {
            $success = $success && copy($testFile->path, $testFileDir . '/' . $testFile->name);
        }
        return $success;
    }

    private function prepareCompileInstructions(): bool
    {
        $compileFile = $this->workingDirBasePath . 'compile.' .
            ($this->studentFile->task->testOS == 'windows' ? 'ps1' : 'sh');

        $result = true;
        if (!empty($this->studentFile->task->compileInstructions)) {
            $result = !empty(file_put_contents($compileFile, $this->studentFile->task->compileInstructions));
            $result = $result && chmod($compileFile, 0755);
        }
        return $result;
    }

    private function prepareRunInstructions(): bool
    {
        $result = true;
        if (!empty($this->studentFile->task->runInstructions)) {
            $runFile = $this->workingDirBasePath . 'run.' .
                ($this->studentFile->task->testOS == 'windows' ? 'ps1' : 'sh');
            $result = !empty(file_put_contents($runFile, $this->studentFile->task->runInstructions));
            $result = $result && chmod($runFile, 0755);
        }
        return $result;
    }

    /**
     * @throws \yii\base\Exception
     */
    private function buildContainer(?string $containerName, ?int $hostPort, ?DockerContainerBuilder $builder): docker\DockerContainer
    {
        if (empty($builder)) {
            $builder = DockerContainerBuilder::forTask($this->studentFile->task);
        };
        if ($this->studentFile->task->appType == Task::APP_TYPE_WEB && !empty($hostPort)) {
            $builder->withHostPort($hostPort);
        }
        return $builder->build($containerName);
    }


    private function copyFiles(docker\DockerContainer $dockerContainer)
    {
        // send student solution to docker container as TAR stream
        $tarPath = $this->workingDirBasePath . 'test.tar';
        $phar = new \PharData($tarPath);
        $phar->buildFromDirectory($this->workingDirBasePath);
        $dockerContainer->uploadArchive(
            $tarPath,
            $this->studentFile->task->testOS == 'windows' ? 'C:\\test' : '/test'
        );
    }

    /**
     * @throws SubmissionRunnerException
     */
    private function execCompile(docker\DockerContainer $dockerContainer)
    {
        if (!empty($this->studentFile->task->compileInstructions)) {
            $compileCommand = [
                'timeout',
                Yii::$app->params['evaluator']['compileTimeout'],
                '/bin/bash',
                '/test/compile.sh'
            ];
            if ($this->studentFile->task->testOS == 'windows') {
                $compileCommand = ['powershell', 'C:\\test\\compile.ps1'];
            }

            $compileResult = $dockerContainer->executeCommand($compileCommand);
            if ($compileResult['exitCode'] != 0) {
                $err = $compileResult['stderr'];
                Yii::info("Failed to compile student file [" . $this->studentFile->id . "]: " . $err, __METHOD__);
                throw new SubmissionRunnerException(
                    'Compile failed',
                    SubmissionRunnerException::COMPILE_FAILURE,
                    $compileResult
                );
            }
        }
    }

    /**
     * @throws SubmissionRunnerException
     */
    private function execWebAppRun(docker\DockerContainer $dockerContainer)
    {
        if (!empty($this->studentFile->task->runInstructions)) {
            //No time out since web app can run for indefinite time
            $runCommand = ['/bin/bash', '-c', '/test/run.sh'];
            if ($this->studentFile->task->testOS == 'windows') {
                $runCommand = ['powershell C:\\test\\run.ps1'];
            }
            $runResult = $dockerContainer->executeCommand($runCommand);
            if ($runResult['exitCode'] != 0) {
                $err = $runResult['stderr'];
                Yii::info("Failed to execute run instruction for student file [ " . $this->studentFile->id . "]: " . $err, __METHOD__);

                throw new SubmissionRunnerException(
                    'Run failed',
                    SubmissionRunnerException::RUN_FAILURE,
                    $runResult
                );
            }
        }
    }

    /**
     * Tries to start run init commands: compile and optional run.
     * If fails the container will be stopped.
     *
     * @param docker\DockerContainer $dockerContainer
     * @return void
     * @throws SubmissionRunnerException
     */
    private function tryInitContainer(docker\DockerContainer $dockerContainer): void
    {
        try {
            $this->copyFiles($dockerContainer);

            $dockerContainer->startContainer();

            $this->execCompile($dockerContainer);

            if (
                $this->studentFile->task->appType == Task::APP_TYPE_WEB
                && !empty($this->studentFile->task->runInstructions)
            ) {
                $this->execWebAppRun($dockerContainer);
            }
        } catch (\Exception $e) {
            $dockerContainer->stopContainer();
            throw $e;
        }
    }


    /**
     * Deletes the student solution and related files from tmp/docker/{studentFileID}
     * @throws \yii\base\ErrorException
     */
    private function deleteSolution()
    {
        $path = $this->workingDirBasePath;
        if (is_dir($path)) {
            FileHelper::removeDirectory($path);
        }
    }
}
