<?php

namespace app\commands;

use app\components\AssignmentTester;
use app\components\CanvasIntegration;
use app\components\docker\DockerImageManager;
use app\components\docker\WebTesterContainer;
use app\components\WebAssignmentTester;
use app\models\InstructorFile;
use app\models\Task;
use app\models\TestCase;
use app\models\StudentFile;
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
     * Runs the automatic tester on the oldest uploaded studentfile.
     * @param int $count Number of studentfiles to evaluate.
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

                // Find the oldest untested studentFile.
                $studentFile = StudentFile::find()
                    ->notTested($IDs)
                    ->orderBy(['uploadCount' => SORT_ASC, 'uploadTime' => SORT_ASC])
                    ->one();

                // If no files to test then return.
                if (!$studentFile) {
                    $this->stdout('No studentFiles found.' . PHP_EOL);
                    return ExitCode::OK;
                }
                if ($studentFile->task->appType == Task::APP_TYPE_CONSOLE) {
                    // Get the test cases for the task.
                    $testCases = TestCase::find()
                        ->where(['taskID' => $studentFile->taskID])
                        ->all();

                    if (!empty($testCases) && empty($studentFile->task->runInstructions)) {
                        ArrayHelper::removeValue($IDs, $studentFile->taskID);
                        $this->stderr(
                            "Test cases found, but the run instruction is missing for task: {$studentFile->task->name} (#{$studentFile->task->id})" . PHP_EOL,
                            Console::FG_RED
                        );
                        $jobFound = false;
                    }
                } else if ($studentFile->task->appType == Task::APP_TYPE_WEB) {
                    $testSuites = InstructorFile::find()
                        ->where(['taskID' => $studentFile->taskID])
                        ->onlyWebAppTestSuites()
                        ->all();
                    if (empty($testSuites)) {
                        ArrayHelper::removeValue($IDs, $studentFile->taskID);
                        $this->stderr(
                            "Test suites are not defined for task: {$studentFile->task->name} (#{$studentFile->task->id})" . PHP_EOL,
                            Console::FG_RED
                        );
                        $jobFound = false;
                    }
                }
            } while (!$jobFound);

            // Mark solution testing as being under execution / in progress
            $studentFile->autoTesterStatus = StudentFile::AUTO_TESTER_STATUS_IN_PROGRESS;
            $studentFile->save();

            // Set locale based on student preference
            $origLanguage = Yii::$app->language;
            Yii::$app->language = $studentFile->uploader->locale;

            if ($studentFile->task->appType == Task::APP_TYPE_CONSOLE) {
                // Run the tests.
                $tester = new AssignmentTester(
                    $studentFile,
                    $testCases,
                    Yii::$app->params['evaluator'][$studentFile->task->testOS]
                );
                $tester->test();
                $result = $tester->getResults();

                $errorMsg = '';
                // If the testing environment couldn't be initialized
                if (!$result['initialized']) {
                    $errorMsg = $result['initiationError'];
                    $studentFile->isAccepted = StudentFile::IS_ACCEPTED_FAILED;
                    $studentFile->autoTesterStatus = StudentFile::AUTO_TESTER_STATUS_INITIATION_FAILED;
                    $studentFile->errorMsg = $errorMsg;
                    // If the solution didn't compile
                } elseif (!$result['compiled']) {
                    $errorMsg = $result['compilationError'];
                    $studentFile->isAccepted = StudentFile::IS_ACCEPTED_FAILED;
                    $studentFile->autoTesterStatus = StudentFile::AUTO_TESTER_STATUS_COMPILATION_FAILED;
                    $studentFile->errorMsg = $errorMsg;
                    // If there were errors executing the program
                } elseif (!$result['executed']) {
                    $errorMsg = $result['errorMsg'];
                    $studentFile->isAccepted = StudentFile::IS_ACCEPTED_FAILED;
                    $studentFile->autoTesterStatus = StudentFile::AUTO_TESTER_STATUS_EXECUTION_FAILED;
                    $studentFile->errorMsg = $errorMsg;
                    // If the tests passed
                } elseif ($result['passed']) {
                    $studentFile->isAccepted = StudentFile::IS_ACCEPTED_PASSED;
                    $studentFile->autoTesterStatus = StudentFile::AUTO_TESTER_STATUS_PASSED;
                    $studentFile->errorMsg = null;
                    // If the tests failed
                } else {
                    $errorMsg = $result['errorMsg'];
                    $studentFile->isAccepted = StudentFile::IS_ACCEPTED_FAILED;
                    $studentFile->autoTesterStatus = StudentFile::AUTO_TESTER_STATUS_TESTS_FAILED;
                    $studentFile->errorMsg = $errorMsg;
                }

                $transaction = Yii::$app->db->beginTransaction();
                try {
                    // Save the results in the database
                    $studentFile->save();

                    // Delete old per test case results from the database
                    TestResult::deleteAll(['studentFileID' => $studentFile->id]);

                    // Save new per test case results in the database
                    if (isset($result['compiled']) && $result['compiled']) {
                        $testCaseNr = 1;
                        foreach ($testCases as $testCase) {
                            if (!isset($result[$testCaseNr])) {
                                continue;
                            }

                            $testResult = new TestResult();
                            $testResult->testCaseID = $testCase->id;
                            $testResult->studentFileID = $studentFile->id;
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
            } else if ($studentFile->task->appType == Task::APP_TYPE_WEB) {
                $webAssignmentTester = new WebAssignmentTester($studentFile);
                $webAssignmentTester->test();
            }

            // Upload the result message to the canvas
            if (Yii::$app->params['canvas']['enabled'] && !empty($studentFile->canvasID)) {
                $canvas = new CanvasIntegration();
                $canvas->uploadTestResultToCanvas($studentFile);
            }

            // Log
            Yii::info(
                "Solution #$studentFile->id evaluated " .
                "for task {$studentFile->task->name} (#$studentFile->taskID) " .
                "with status $studentFile->isAccepted",
                __METHOD__
            );

            // E-mail notification
            if (!empty($studentFile->uploader->notificationEmail)) {
                Yii::$app->mailer->compose(
                    'student/checkSolution',
                    [
                        'studentFile' => $studentFile
                    ]
                )
                    ->setFrom(Yii::$app->params['systemEmail'])
                    ->setTo($studentFile->uploader->notificationEmail)
                    ->setSubject(Yii::t('app/mail', 'Automated submission test ready'))
                    ->send();
            }

            // Return the results in JSON for debugging
            $result['studentName'] = $studentFile->uploader->name;
            $result['studentNeptun'] = $studentFile->uploader->neptun;

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
