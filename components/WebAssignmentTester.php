<?php

namespace app\components;

use app\components\docker\DockerContainerBuilder;
use app\components\docker\DockerNetwork;
use app\components\docker\WebTesterContainer;
use app\exceptions\DockerContainerException;
use app\exceptions\SubmissionRunnerException;
use app\models\TaskFile;
use app\models\Submission;
use PHPUnit\TextUI\Exception;
use Yii;
use yii\helpers\FileHelper;

/**
 * Test runner for web app tasks
 */
class WebAssignmentTester
{
    private Submission $submission;
    private string $workDir;
    private SubmissionRunner $submissionRunner;

    private DockerNetwork $dockerNetwork;
    private docker\DockerContainer $applicationUnderTest;
    private docker\WebTesterContainer $testRunner;

    /**
     * construct
     * @param Submission $submission
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\di\NotInstantiableException
     */
    public function __construct(Submission $submission)
    {
        $this->submission = $submission;
        $this->workDir = $this->initWorkDir();
        $this->submissionRunner = Yii::$container->get(SubmissionRunner::class);
    }

    /**
     * Executes tests against student's submission
     *
     * @return void
     * @throws \yii\base\ErrorException
     * @throws \yii\base\Exception
     * @throws \Exception
     */
    public function test()
    {
        try {
            $testsPath = $this->prepareTestSuites();
            $this->initSystemUnderTest();
            $this->initTestRunner();
            $this->evaluate($testsPath);
        } catch (SubmissionRunnerException $e) {
            $this->handleSubmissionRunnerException($e);
        } catch (DockerContainerException $e) {
            $this->submission->autoTesterStatus = Submission::AUTO_TESTER_STATUS_INITIATION_FAILED;
            $this->submission->save();
        } finally {
            $this->updateReports();
            $this->tearDown();
        }
    }

    /**
     * Creates env and launches the web application
     *
     * @return void
     * @throws SubmissionRunnerException
     * @throws \yii\base\ErrorException
     * @throws \yii\base\Exception
     */
    private function initSystemUnderTest()
    {
        $this->dockerNetwork = DockerNetwork::createWithDefaultBridgeConfig(
            $this->submission->task->testOS,
            'tms_network_' . $this->submission->id
        );

        $builder = DockerContainerBuilder::forTask($this->submission->task)
            ->withNetworkMode($this->dockerNetwork->getNetworkInspectResult()->getId());

        $this->applicationUnderTest = $this->submissionRunner
            ->run($this->submission, null, null, $builder);
    }

    /**
     * Creates the test runner container
     *
     * @return void
     * @throws \yii\base\ErrorException
     * @throws \yii\base\Exception
     */
    private function initTestRunner()
    {
        $os = $this->submission->task->testOS;
        $webAppPort = $this->submission->task->port;
        $this->testRunner = WebTesterContainer::createInstanceForTest($os, $this->applicationUnderTest, $webAppPort);
    }

    /**
     * Run tests and store execution results.
     * @return void
     */
    private function evaluate($testsPath)
    {
        $result = $this->testRunner->runTests($testsPath);

        if ($result['exitCode'] == 0) {
            $this->submission->status = Submission::STATUS_PASSED;
            $this->submission->autoTesterStatus = Submission::AUTO_TESTER_STATUS_PASSED;
        } else {
            $this->submission->status = Submission::STATUS_FAILED;
            $this->submission->autoTesterStatus = Submission::AUTO_TESTER_STATUS_TESTS_FAILED;
        }
        $this->submission->errorMsg = Yii::t('app', 'Check web reports for details.');
        $this->submission->save();
    }

    /**
     * Update reports with the latest test run. Old reports are always deleted, even if there aren't any new test reports.
     * @return void
     */
    private function updateReports()
    {
        $reportPath = $this->submission->reportPath;
        $basepath = dirname($reportPath);

        try {
            if (file_exists($basepath)) {
                FileHelper::removeDirectory($basepath);
            }
            if (!empty($this->testRunner)) {
                mkdir($basepath, 0755, true);
                $this->testRunner->downloadTestReports($reportPath);
            }
        } catch (\Exception $e) {
            Yii::error(
                'Failed to update web test reports: ' . $e->getMessage() . ', ' . $e->getTraceAsString(),
                __METHOD__
            );
        }
    }

    /**
     * Handle compile and run failures.
     * @param SubmissionRunnerException $exception
     * @return void
     */
    private function handleSubmissionRunnerException(SubmissionRunnerException $exception): void
    {
        $this->submission->status = Submission::STATUS_FAILED;
        $this->submission->errorMsg = $exception->getStderr();
        switch ($exception->getCode()) {
            case SubmissionRunnerException::COMPILE_FAILURE:
                $this->submission->autoTesterStatus = Submission::AUTO_TESTER_STATUS_COMPILATION_FAILED;
                break;
            case SubmissionRunnerException::RUN_FAILURE:
            case SubmissionRunnerException::PREPARE_FAILURE:
                $this->submission->autoTesterStatus = Submission::AUTO_TESTER_STATUS_EXECUTION_FAILED;
                break;
            default:
                Yii::error(
                    'Unhandled SubmissionRunnerException code: '
                    . $exception->getCode()
                    . ': ' . $exception->getMessage()
                    . ' ' . $exception->getTraceAsString(),
                    __METHOD__
                );
        }
        $this->submission->save();
    }

    /**
     * Free up resources
     * @return void
     */
    private function tearDown()
    {
        $containerName = $this->testRunner->getContainerName();
        try {
            if (!empty($this->testRunner)) {
                Yii::info("Deleting rest runner container [$containerName]", __METHOD__);
                $this->testRunner->tearDown();
                Yii::info("Test runner container [$containerName] deleted", __METHOD__);
            }
        } catch (\Exception $e) {
            Yii::error("Failed to delete container [$containerName]", __METHOD__);
        }

        $containerName = $this->applicationUnderTest->getContainerName();
        try {
            if (!empty($this->applicationUnderTest)) {
                Yii::info(
                    "Deleting system under test container [$containerName",
                    __METHOD__
                );
                $this->applicationUnderTest->stopContainer();
                Yii::info(
                    "System under test container [$containerName] deleted",
                    __METHOD__
                );
            }
        } catch (\Exception $e) {
            Yii::error(
                "Failed to delete system under test container [$containerName]",
                __METHOD__
            );
        }

        $networkId = $this->dockerNetwork->getNetworkInspectResult()->getId();
        try {
            if (!empty($this->dockerNetwork)) {
                Yii::info("Deleting docker network [$networkId]", __METHOD__);
                $this->dockerNetwork->deleteNetwork();
                Yii::info("Docker network [$networkId] deleted", __METHOD__);
            }
        } catch (\Exception $e) {
            Yii::error("Failed to delete network [$networkId]", __METHOD__);
        }

        $workDir = $this->workDir;
        try {
            Yii::info("Deleting working dir [$workDir]", __METHOD__);
            if (file_exists($this->workDir)) {
                FileHelper::removeDirectory($this->workDir);
            }
            Yii::info("Working dir [$workDir] deleted", __METHOD__);
        } catch (\Exception $e) {
            Yii::error("Failed to delete workdir [$workDir]", __METHOD__);
        }
    }

    public function prepareTestSuites(): string
    {
        $suitesDirName = WebTesterContainer::SUITES_DIR_NAME;
        if (!file_exists($this->workDir . '/' . $suitesDirName)) {
            mkdir($this->workDir . '/' . $suitesDirName, 0755, true);
        }
        $suiteFiles = TaskFile::find()
            ->where(['taskID' => $this->submission->taskID])
            ->onlyWebAppTestSuites()
            ->all();

        $result = true;
        foreach ($suiteFiles as $suiteFile) {
            $result = $result && copy(
                $suiteFile->path,
                $this->workDir . '/' .  $suitesDirName . '/' . $suiteFile->name
            );
        }

        $tarPath = $this->workDir . '/test.tar';
        $phar = new \PharData($tarPath);
        $phar->buildFromDirectory($this->workDir);

        return $tarPath;
    }

    private function initWorkDir(): string
    {
        $randomName = Yii::$app->security->generateRandomString(4);
        $path = Yii::getAlias("@appdata/tmp/docker/$randomName");
        mkdir($path, 0755, true);
        return $path;
    }
}
