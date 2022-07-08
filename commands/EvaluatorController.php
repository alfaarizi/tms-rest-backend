<?php

namespace app\commands;

use app\components\AssignmentTester;
use app\components\CanvasIntegration;
use app\models\Task;
use app\models\TestCase;
use app\models\StudentFile;
use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Console;
use yii\console\ExitCode;
use yii\console\widgets\Table;

class EvaluatorController extends BaseController
{
    /**
     * Runs the automatic tester on the oldest uploaded studentfile.
     * @param int $count Number of studentfiles to evaluate.
     * @return int Error code.
     */
    public function actionCheck($count = 1)
    {
        if (!Yii::$app->params['evaluator']['enabled']) {
            $this->stderr('Automatic evaluator is disabled in configuration.' . PHP_EOL, Console::FG_RED);
            return ExitCode::CONFIG;
        }

        // Get the IDs of tasks with autoTest enabled.
        $IDs = array_keys(
            (new \yii\db\Query())
                ->select('id')
                ->from('{{%tasks}}')
                ->where(['autoTest' => 1])
                ->andWhere(['not', ['imageName' => null]])
                ->andWhere(['not', ['compileInstructions' => null]])
                ->andWhere(['not', ['appType' => Task::APP_TYPE_WEB]])
                ->indexBy('id')
                ->all()
        );

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
                    ->where(
                        [
                            'isAccepted' => StudentFile::IS_ACCEPTED_UPLOADED,
                            'taskID' => $IDs
                        ]
                    )
                    ->orderBy(['uploadCount' => SORT_ASC, 'uploadTime' => SORT_ASC])
                    ->one();

                // If no files to test then return.
                if (!$studentFile) {
                    $this->stdout('No studentFiles found.' . PHP_EOL);
                    return ExitCode::OK;
                }

                // Get the test cases for the task.
                $testCases = TestCase::find()
                    ->where(['taskID' => $studentFile->taskID])
                    ->all();

                if (!empty($testCases) && empty($studentFile->task->runInstructions)) {
                    ArrayHelper::removeValue($IDs, $studentFile->task->id);
                    $this->stderr(
                        "Test cases found, but the run instruction is missing for task: {$studentFile->task->name} (#{$studentFile->task->id})" . PHP_EOL,
                        Console::FG_RED
                    );
                    $jobFound = false;
                }
            } while (!$jobFound);

            // Set locale based on student preference
            $origLanguage = Yii::$app->language;
            Yii::$app->language = $studentFile->uploader->locale;

            // Run the tests.
            $tester = new AssignmentTester(
                $studentFile,
                $testCases,
                Yii::$app->params['evaluator'][$studentFile->task->testOS]
            );
            $tester->test();
            $result = $tester->getResults();

            $errorMsg = '';
            // If the solution didn't compile
            if (!$result['compiled']) {
                $errorMsg = $result['compilationError'];
                $studentFile->isAccepted = StudentFile::IS_ACCEPTED_FAILED;
                $studentFile->evaluatorStatus = StudentFile::EVALUATOR_STATUS_COMPILATION_FAILED;
                $studentFile->errorMsg = $errorMsg;
                // If there were errors executing the program
            } elseif ($result['error'] && $result['compiled']) {
                $errorMsg = $result['errorMsg'];
                $studentFile->isAccepted = StudentFile::IS_ACCEPTED_FAILED;
                $studentFile->evaluatorStatus = StudentFile::EVALUATOR_STATUS_EXECUTION_FAILED;
                $studentFile->errorMsg = $errorMsg;
                // If the solution compiled and there were no errors
            } else {
                if (!$result['error'] && $result['compiled']) {
                    // If the solution passed
                    if ($result['passed']) {
                        $studentFile->isAccepted = StudentFile::IS_ACCEPTED_PASSED;
                        $studentFile->evaluatorStatus = StudentFile::EVALUATOR_STATUS_PASSED;
                        $studentFile->errorMsg = null;
                    } else {
                        $errorMsg = $result['errorMsg'];
                        $studentFile->isAccepted = StudentFile::IS_ACCEPTED_FAILED;
                        $studentFile->evaluatorStatus = StudentFile::EVALUATOR_STATUS_TESTS_FAILED;
                        $studentFile->errorMsg = $errorMsg;
                    }
                }
            }

            // Save the results in the database
            $studentFile->save();

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
            return ExitCode::OK;
        }
    }
}
