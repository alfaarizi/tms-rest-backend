<?php

namespace app\commands;

use app\components\AssignmentTester;
use app\components\CanvasIntegration;
use app\models\AccessToken;
use app\models\Group;
use app\models\TestCase;
use app\models\StudentFile;
use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Console;
use yii\console\ExitCode;
use yii\console\widgets\Table;
use yii\db\Expression;

/**
 * Manages scheduled tasks.
 */
class CronController extends BaseController
{
    /**
     * Sends digest email notifications to instructors about new student solutions.
     *
     * @param int $hours Time interval to analyze.
     * @param null $neptun Instructor to analyze. (Null for all.)
     * @return int Error code.
     */
    public function actionDigestInstructors($hours = 24, $neptun = null)
    {
        // Query
        $query = StudentFile::find()
            ->alias('sf')
            ->joinWith('task t')
            ->joinWith('task.group g')
            ->joinWith('task.group.instructors u')
            ->where(
                [
                    'sf.isAccepted' =>
                        [
                            StudentFile::IS_ACCEPTED_UPLOADED,
                            StudentFile::IS_ACCEPTED_UPDATED,
                            StudentFile::IS_ACCEPTED_PASSED,
                            StudentFile::IS_ACCEPTED_FAILED,
                        ]
                ]
            )
            ->andWhere([
                '>',
                'sf.uploadTime',
                new Expression('DATE_SUB(NOW(), INTERVAL :digest HOUR)', [':digest' => $hours])
            ])
            ->orderBy('u.neptun')
            ->addOrderBy('sf.uploadTime');

        if (strlen($neptun)) {
            $query = $query->andWhere(['u.neptun' => $neptun]);
        }

        // Load data
        $newSolutions = $query->all();
        $count = count($newSolutions);

        if ($count == 0) {
            $this->stdout("No new solutions has been submitted." . PHP_EOL);
        } else {
            $this->stdout("$count new solution(s) has been submitted." . PHP_EOL);

            // Show data
            $table = new Table();
            $table->setHeaders(['Neptun', 'Name', 'Status', 'Upload Time', 'Instructor Name']);

            $rows = [];
            foreach ($newSolutions as $solution) {
                /** @var \app\models\StudentFile $solution */
                foreach ($solution->task->group->instructors as $instructor) {
                    if (strlen($neptun) && strtolower($neptun) != strtolower($instructor->neptun)) {
                        continue;
                    }

                    $rows[] = [
                        $solution->uploader->neptun,
                        $solution->uploader->name,
                        $solution->isAccepted,
                        $solution->uploadTime,
                        $instructor->name
                    ];
                }
            }
            echo $table->setRows($rows)
                ->run();

            $sendEmails = true;
            if ($this->interactive) {
                $sendEmails = $this->promptBoolean('Send digest email notifications now?');
            }

            // Email notifications
            if ($sendEmails) {
                /** @var \app\models\User[] $instructors */
                $instructors = [];
                $solutionsByInstructor = [];
                foreach ($newSolutions as $solution) {
                    foreach ($solution->task->group->instructors as $instructor) {
                        if (strlen($neptun) && strtolower($neptun) != strtolower($instructor->neptun)) {
                            continue;
                        }

                        $instructors[$instructor->neptun] = $instructor;
                        $solutionsByInstructor[$instructor->neptun][] = $solution;
                    }
                }

                $messages = [];
                $origLanguage = Yii::$app->language;
                foreach ($solutionsByInstructor as $neptun => $solutions) {
                    /** @var \app\models\StudentFile[] $solutions */
                    if (!empty($instructors[$neptun]->notificationEmail)) {
                        Yii::$app->language = $instructors[$neptun]->locale;
                        $messages[] = Yii::$app->mailer->compose('instructor/digestSolution', [
                            'solutions' => $solutions,
                            'hours' => $hours,
                        ])
                            ->setFrom(Yii::$app->params['systemEmail'])
                            ->setTo($instructors[$neptun]->notificationEmail)
                            ->setSubject(Yii::t('app/mail', 'Submitted solutions'));
                    }
                }
                Yii::$app->language = $origLanguage;

                // Send mass email notifications
                $sentCount = Yii::$app->mailer->sendMultiple($messages);
                $this->stdout("$sentCount email(s) has been sent." . PHP_EOL, Console::FG_GREEN);
            }
        }
        return ExitCode::OK;
    }

    /**
     * Runs the automatic tester on the oldest uploaded studentfile.
     * @param int $count Number of studentfiles to evaluate.
     * @return int Error code.
     */
    public function actionCheckSubmission($count = 1)
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
                            'isAccepted' => [StudentFile::IS_ACCEPTED_UPLOADED, StudentFile::IS_ACCEPTED_UPDATED],
                            'taskID' => $IDs
                        ]
                    )
                    ->orderBy('uploadTime')
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

    /**
     * Runs the automatic synchronization with canvas
     * @param null $groupId Group to synchronize (empty for all)
     */
    public function actionCanvasSynchronization($groupId = null)
    {
        $groupQuery = Group::find()
            ->alias('g')
            ->joinWith('semester s')
            ->where(['IS NOT', 'canvasCourseID', null])
            ->andWhere(['actual' => true])
            ->orderBy('synchronizerID');

        if ($groupId) {
            $groupQuery = $groupQuery->andWhere(['g.id' => $groupId]);
        }

        $canvasGroups = $groupQuery->all();
        $this->stdout("Synchronizing " . count($canvasGroups) . " group(s)." . PHP_EOL);

        $synchronizer = null;
        $hasToken = false;
        foreach ($canvasGroups as $group) {
            $canvas = new CanvasIntegration();
            if ($synchronizer !== $group->synchronizerID) {
                $this->stdout("Fetching token for user {$group->synchronizer->neptun} (ID: #{$group->synchronizer->id})" . PHP_EOL);
                $hasToken = $canvas->refreshCanvasToken($group->synchronizer);
                $synchronizer = $group->synchronizerID;
            }

            if ($hasToken) {
                $this->stdout("Synchronizing group #{$group->id}" . PHP_EOL);
                $canvas->synchronizeGroupData($group);
                sleep(10);
            } else {
                $this->stderr("Failed to synchronize group #{$group->id} for user {$group->synchronizer->neptun} (ID: #{$group->synchronizer->id})" . PHP_EOL, Console::FG_RED);
            }
        }
        return ExitCode::OK;
    }

    /**
     * Deletes expired access tokens from the database
     * @return int
     */
    public function actionClearExpiredAccessTokens()
    {
        $count = AccessToken::deleteAll('validUntil < NOW()');

        // Log
        Yii::info(
            "Successfully deleted $count expired access tokens from database",
            __METHOD__
        );

        return ExitCode::OK;
    }
}
