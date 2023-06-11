<?php

namespace app\components;

use app\components\docker\DockerContainerBuilder;
use app\components\docker\EvaluatorTarBuilder;
use app\exceptions\EvaluatorTarBuilderException;
use app\exceptions\SubmissionRunnerException;
use app\models\StudentFile;
use app\models\Task;
use Yii;
use yii\base\Exception;

/**
 * Class to create a running instance of a Docker container populated with student's submission
 */
class SubmissionRunner
{
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
     */
    public function run(StudentFile $studentFile, ?int $hostPort = null, ?string $containerName = null, ?DockerContainerBuilder $builder = null): docker\DockerContainer
    {
        $this->studentFile = $studentFile;

        $dockerContainer = null;
        try {
            $dockerContainer = $this->buildContainer($containerName, $hostPort, $builder);
            $this->tryInitContainer($dockerContainer);
        } catch (EvaluatorTarBuilderException $e) {
            throw new SubmissionRunnerException(
                Yii::t('app', 'File prepare failed'),
                SubmissionRunnerException::PREPARE_FAILURE
            );
        } catch (SubmissionRunnerException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new SubmissionRunnerException(
                Yii::t('app', 'Container initialization failed'),
                SubmissionRunnerException::PREPARE_FAILURE,
                null,
                $e
            );
        }

        return $dockerContainer;
    }

    /**
     * @throws Exception
     */
    private function buildContainer(?string $containerName, ?int $hostPort, ?DockerContainerBuilder $builder): docker\DockerContainer
    {
        if (empty($builder)) {
            $builder = DockerContainerBuilder::forTask($this->studentFile->task);
        }
        if ($this->studentFile->task->appType == Task::APP_TYPE_WEB && !empty($hostPort)) {
            $builder->withHostPort($hostPort);
        }
        return $builder->build($containerName);
    }


    /**
     * Send student solution, test files and scripts to docker container as TAR stream
     * @param docker\DockerContainer $dockerContainer
     * @return void
     * @throws EvaluatorTarBuilderException File preparation failed
     */
    private function copyFiles(docker\DockerContainer $dockerContainer)
    {
        $tarBuilder = new EvaluatorTarBuilder(
            Yii::$app->basePath
            . '/'
            . Yii::$app->params['data_dir']
            . '/tmp/docker/',
            Yii::$app->security->generateRandomString(4)
        );
        $task = $this->studentFile->task;
        $ext = $task->testOS == 'windows' ? '.ps1' : '.sh';
        try {
            $tarPath = $tarBuilder
                ->withSubmission($this->studentFile->getPath())
                ->withInstructorTestFiles($task->id)
                ->withTextFile('compile' . $ext, $task->compileInstructions, true)
                ->withTextFile('run' . $ext, $task->runInstructions, true)
                ->buildTar();

            $dockerContainer->uploadArchive(
                $tarPath,
                $this->studentFile->task->testOS == 'windows' ? 'C:\\test' : '/test'
            );
        } finally {
            $tarBuilder->cleanup();
        }
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
                if ($this->studentFile->task->testOS == 'linux') {
                    $err = !empty($compileResult['stderr']) ? $compileResult['stderr'] : $compileResult['stdout'];
                } else { // == 'windows'
                    $err = $compileResult['stdout'];
                }
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
                if ($this->studentFile->task->testOS == 'linux') {
                    $err = !empty($runResult['stderr']) ? $runResult['stderr'] : $runResult['stdout'];
                } else { // == 'windows'
                    $err = $runResult['stdout'];
                }
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
     * @throws EvaluatorTarBuilderException
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
}
