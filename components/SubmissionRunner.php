<?php

namespace app\components;

use app\components\docker\DockerContainerBuilder;
use app\components\docker\EvaluatorTarBuilder;
use app\exceptions\EvaluatorTarBuilderException;
use app\exceptions\SubmissionRunnerException;
use app\models\Submission;
use app\models\Task;
use Yii;
use yii\base\Exception;

/**
 * Class to create a running instance of a Docker container populated with student's submission
 */
class SubmissionRunner
{
    private string $workingDirBasePath;
    private Submission $submission;

    /**
     * Creates the run instruction log path given to the specific os type
     * @param string $os
     * @return string
     */
    public static function getWebappRunLogPath(string $os): string
    {
        if ($os == 'linux') {
            return '/test/run.log';
        } else {
            return 'C:\\test\\run.log';
        }
    }

    /**
     * Starts a docker container with the student's submission.
     *
     * @param Submission $submission
     * @param int|null $hostPort optional host port to bind - ignored if not web app. Overrides builder configuration
     * @param string|null $containerName optional container name, if null the container name will be generated
     * @param DockerContainerBuilder|null $builder optional builder, if null container will be created with Task defaults
     *
     * @return docker\DockerContainer
     *
     * @throws SubmissionRunnerException
     */
    public function run(Submission $submission, ?int $hostPort = null, ?string $containerName = null, ?DockerContainerBuilder $builder = null): docker\DockerContainer
    {
        $this->submission = $submission;

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
            $builder = DockerContainerBuilder::forTask($this->submission->task);
        }
        if ($this->submission->task->appType == Task::APP_TYPE_WEB && !empty($hostPort)) {
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
            Yii::getAlias("@tmp/docker/"),
            Yii::$app->security->generateRandomString(4)
        );
        $task = $this->submission->task;
        $ext = $task->testOS == 'windows' ? '.ps1' : '.sh';
        try {
            $tarPath = $tarBuilder
                ->withSubmission($this->submission->getPath())
                ->withInstructorTestFiles($task->id)
                ->withTextFile('compile' . $ext, $task->compileInstructions, true)
                ->withTextFile('run' . $ext, $task->runInstructions, true)
                ->buildTar();

            $dockerContainer->uploadArchive(
                $tarPath,
                $this->submission->task->testOS == 'windows' ? 'C:\\test' : '/test'
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
        if (!empty($this->submission->task->compileInstructions)) {
            $compileCommand = [
                'timeout',
                strval(Yii::$app->params['evaluator']['compileTimeout']),
                '/bin/bash',
                '/test/compile.sh'
            ];
            if ($this->submission->task->testOS == 'windows') {
                $compileCommand = ['powershell', 'C:\\test\\compile.ps1'];
            }

            $compileResult = $dockerContainer->executeCommand($compileCommand);
            if ($compileResult['exitCode'] != 0) {
                if ($this->submission->task->testOS == 'linux') {
                    $err = !empty($compileResult['stderr']) ? $compileResult['stderr'] : $compileResult['stdout'];
                } else { // == 'windows'
                    $err = $compileResult['stdout'];
                }
                Yii::info("Failed to compile student file [" . $this->submission->id . "]: " . $err, __METHOD__);
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
        if (!empty($this->submission->task->runInstructions)) {
            $logPath = SubmissionRunner::getWebappRunLogPath($this->submission->task->testOS);

            //No time out since web app can run for indefinite time
            $runCommand = ['/bin/bash', '-c', "/test/run.sh >> $logPath"];
            if ($this->submission->task->testOS == 'windows') {
                $runCommand = ["powershell C:\\test\\run.ps1 | Out-File -FilePath $logPath"];
            }
            $runResult = $dockerContainer->executeCommand($runCommand, false);
            if ($runResult['exitCode'] != 0) {
                if ($this->submission->task->testOS == 'linux') {
                    $err = !empty($runResult['stderr']) ? $runResult['stderr'] : $runResult['stdout'];
                } else { // == 'windows'
                    $err = $runResult['stdout'];
                }
                Yii::info("Failed to execute run instruction for student file [ " . $this->submission->id . "]: " . $err, __METHOD__);

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

            if ($this->submission->task->testOS === 'linux') {
                $dockerContainer->executeCommand(['chmod', '+x', '/test/compile.sh', '/test/run.sh']);
            }

            $this->execCompile($dockerContainer);

            if (
                $this->submission->task->appType == Task::APP_TYPE_WEB
                && !empty($this->submission->task->runInstructions)
            ) {
                $this->execWebAppRun($dockerContainer);
            }
        } catch (\Exception $e) {
            $dockerContainer->stopContainer();
            throw $e;
        }
    }
}
