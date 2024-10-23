<?php

namespace app\commands;

use app\components\AssignmentTester;
use app\components\CanvasIntegration;
use app\components\docker\DockerImageManager;
use app\components\docker\WebTesterContainer;
use app\components\WebAssignmentTester;
use app\models\TaskFile;
use app\models\Task;
use app\models\TestCase;
use app\models\Submission;
use app\models\TestResult;
use Yii;
use yii\base\InvalidConfigException;
use yii\di\NotInstantiableException;
use yii\helpers\ArrayHelper;
use yii\helpers\Console;
use yii\console\ExitCode;
use yii\console\widgets\Table;

class AutoTesterController extends BaseController
{
    /**
     * Runs the automatic tester on the oldest uploaded submission.
     * @param int $count Number of submission to evaluate.
     * @return int Error code.
     */
    public function actionCheck(int $count = 1): int
    {
        if (!Yii::$app->params['evaluator']['enabled']) {
            $this->stderr('Evaluator is disabled in configuration.' . PHP_EOL, Console::FG_RED);
            return ExitCode::CONFIG;
        }

        // Get the IDs of tasks with autoTest enabled.
        $IDs = array_keys(
            Task::find()
                ->select('id')
                ->autoTestEnabled()
                ->asArray()
                ->indexBy('id')
                ->all()
        );

        $testCases = [];
        for ($i = 0; $i < $count; ++$i) {
            // Find a job
            do {
                $jobFound = true;
                if (empty($IDs)) {
                    $this->stdout('No tasks found.' . PHP_EOL);
                    return ExitCode::OK;
                }

                // Find the oldest untested submission.
                /** @var null|Submission $submission */
                $submission = Submission::find()
                    ->notTested($IDs)
                    ->orderBy(['uploadCount' => SORT_ASC, 'uploadTime' => SORT_ASC])
                    ->one();

                // If no files to test then return.
                if (!$submission) {
                    $this->stdout('No submission found.' . PHP_EOL);
                    return ExitCode::OK;
                }
                if ($submission->task->appType == Task::APP_TYPE_CONSOLE) {
                    // Get the test cases for the task.
                    /** @var TestCase[] $testCases */
                    $testCases = TestCase::find()
                        ->where(['taskID' => $submission->taskID])
                        ->all();

                    if (!empty($testCases) && empty($submission->task->runInstructions)) {
                        ArrayHelper::removeValue($IDs, $submission->taskID);
                        $this->stderr(
                            "Test cases found, but the run instruction is missing for task: {$submission->task->name} (#{$submission->task->id})" . PHP_EOL,
                            Console::FG_RED
                        );
                        $jobFound = false;
                    }
                } else if ($submission->task->appType == Task::APP_TYPE_WEB) {
                    $testSuites = TaskFile::find()
                        ->where(['taskID' => $submission->taskID])
                        ->onlyWebAppTestSuites()
                        ->all();
                    if (empty($testSuites)) {
                        ArrayHelper::removeValue($IDs, $submission->taskID);
                        $this->stderr(
                            "Test suites are not defined for task: {$submission->task->name} (#{$submission->task->id})" . PHP_EOL,
                            Console::FG_RED
                        );
                        $jobFound = false;
                    }
                }
            } while (!$jobFound);

            // Mark solution testing as being under execution / in progress
            $submission->autoTesterStatus = Submission::AUTO_TESTER_STATUS_IN_PROGRESS;
            $submission->save();

            // Set locale based on student preference
            $origLanguage = Yii::$app->language;
            Yii::$app->language = $submission->uploader->locale;

            if ($submission->task->appType == Task::APP_TYPE_CONSOLE) {
                // Run the tests.
                $tester = new AssignmentTester(
                    $submission,
                    $testCases,
                    Yii::$app->params['evaluator'][$submission->task->testOS]
                );
                $tester->test();
                $result = $tester->getResults();

                $errorMsg = '';
                // If the testing environment couldn't be initialized
                if (!$result['initialized']) {
                    $errorMsg = $result['initiationError'];
                    $submission->status = Submission::STATUS_FAILED;
                    $submission->autoTesterStatus = Submission::AUTO_TESTER_STATUS_INITIATION_FAILED;
                    $submission->errorMsg = $errorMsg;
                    // If the solution didn't compile
                } elseif (!$result['compiled']) {
                    $errorMsg = $result['compilationError'];
                    $submission->status = Submission::STATUS_FAILED;
                    $submission->autoTesterStatus = Submission::AUTO_TESTER_STATUS_COMPILATION_FAILED;
                    $submission->errorMsg = $errorMsg;
                    // If there were errors executing the program
                } elseif (!$result['executed']) {
                    $errorMsg = $result['errorMsg'];
                    $submission->status = Submission::STATUS_FAILED;
                    $submission->autoTesterStatus = Submission::AUTO_TESTER_STATUS_EXECUTION_FAILED;
                    $submission->errorMsg = $errorMsg;
                    // If the tests passed
                } elseif ($result['passed']) {
                    $submission->status = Submission::STATUS_PASSED;
                    $submission->autoTesterStatus = Submission::AUTO_TESTER_STATUS_PASSED;
                    // If the tests failed
                } else {
                    $errorMsg = $result['errorMsg'];
                    $submission->status = Submission::STATUS_FAILED;
                    $submission->autoTesterStatus = Submission::AUTO_TESTER_STATUS_TESTS_FAILED;
                    $submission->errorMsg = $errorMsg;
                }

                $transaction = Yii::$app->db->beginTransaction();
                try {
                    // Save the results in the database
                    $submission->save();

                    // Delete old per test case results from the database
                    TestResult::deleteAll(['submissionID' => $submission->id]);

                    // Save new per test case results in the database
                    if (isset($result['compiled']) && $result['compiled']) {
                        $testCaseNr = 1;
                        foreach ($testCases as $testCase) {
                            if (!isset($result[$testCaseNr])) {
                                continue;
                            }

                            $testResult = new TestResult();
                            $testResult->testCaseID = $testCase->id;
                            $testResult->submissionID = $submission->id;
                            $testResult->isPassed = $result[$testCaseNr]['passed'];
                            $testResult->errorMsg = $result[$testCaseNr]['errorMsg'];
                            $testResult->save();

                            $testCaseNr++;
                        }
                    }
                    $transaction->commit();
                } catch (\Exception $e) {
                    $transaction->rollBack();
                    throw $e;
                }
            } else if ($submission->task->appType == Task::APP_TYPE_WEB) {
                $webAssignmentTester = new WebAssignmentTester($submission);
                $webAssignmentTester->test();
            }

            // Upload the result message to the canvas
            if (Yii::$app->params['canvas']['enabled'] && !empty($submission->canvasID)) {
                $canvas = new CanvasIntegration();
                $canvas->uploadTestResultToCanvas($submission);
            }

            // Log
            Yii::info(
                "Solution #$submission->id evaluated " .
                "for task {$submission->task->name} (#$submission->taskID) " .
                "with status $submission->status",
                __METHOD__
            );

            // E-mail notification
            if (!empty($submission->uploader->notificationEmail)) {
                Yii::$app->mailer->compose(
                    'student/checkSolution',
                    [
                        'submission' => $submission
                    ]
                )
                    ->setFrom(Yii::$app->params['systemEmail'])
                    ->setTo($submission->uploader->notificationEmail)
                    ->setSubject(Yii::t('app/mail', 'Automated submission test ready'))
                    ->send();
            }

            // Return the results in JSON for debugging
            $result['studentName'] = $submission->uploader->name;
            $result['studentUserCode'] = $submission->uploader->userCode;

            // Show data
            $table = new Table();
            $table->setHeaders(['Field', 'Value']);

            $rows = [];
            foreach ($result as $key => $value) {
                if (!is_int($key)) {
                    $rows[] = [$key, $value];
                } else {
                    $rows[] = ["Test #$key", $value['passed']];
                }
            }
            $table->setRows($rows);
            echo $table->run();

            Yii::$app->language = $origLanguage;
        }
        return ExitCode::OK;
    }

    /**
     * Pulls the Robot Framework image for the web application tester
     * @param string $os
     * @return int
     * @throws \Exception
     */
    public function actionPullWebTesterImage(string $os): int
    {
        if (!Yii::$app->params['evaluator']['enabled']) {
            $this->stderr('Evaluator is disabled in configuration.' . PHP_EOL, Console::FG_RED);
            return ExitCode::CONFIG;
        }

        $imageName = WebTesterContainer::getTesterImageName($os);
        if (empty($imageName)) {
            $this->stderr(
                'Web tester Docker image is not set in configuration for the given os: ' . $os . PHP_EOL,
                Console::FG_RED
            );
            return ExitCode::CONFIG;
        }

        try {
            $this->stdout("Pulling image: $imageName" . PHP_EOL);
            $dockerImageManager = Yii::$container->get(DockerImageManager::class, ['os' => $os]);
            $dockerImageManager->pullImage($imageName);
            $this->stdout("Pulled image: $imageName" . PHP_EOL, Console::FG_GREEN);
        } catch (NotInstantiableException | InvalidConfigException $e) {
            $this->stderr("Unable to get DockerImageManager from the DI container: {$e->getMessage()}" . PHP_EOL);
            return ExitCode::CONFIG;
        } catch (\Throwable $e) {
            $this->stderr("Unexpected error, failed to pull docker image: {$e->getMessage()}" . PHP_EOL);
            return  ExitCode::UNSPECIFIED_ERROR;
        }

        return ExitCode::OK;
    }
}
