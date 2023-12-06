<?php

namespace app\commands;

use app\components\DueSubmissionDigester;
use app\models\StudentFile;
use yii\console\ExitCode;
use yii\console\widgets\Table;
use yii\db\Expression;
use yii\helpers\Console;
use Yii;

class NotificationController extends BaseController
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
                            StudentFile::IS_ACCEPTED_PASSED,
                            StudentFile::IS_ACCEPTED_FAILED,
                            StudentFile::IS_ACCEPTED_CORRUPTED,
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
        /** @var StudentFile[] $newSolutions */
        $newSolutions = $query->all();
        $count = count($newSolutions);

        if ($count == 0) {
            $this->stdout("No new solutions has been submitted." . PHP_EOL);
        } else {
            $this->stdout("$count new solution(s) has been submitted." . PHP_EOL);

            $corruptedSolutions = array_filter($newSolutions, function ($solution) {
                return $solution->isAccepted == StudentFile::IS_ACCEPTED_CORRUPTED;
            });
            $corruptedCount = count($corruptedSolutions);


            if ($corruptedCount > 0) {
                $this->stdout("$corruptedCount new corrupted solution(s) has been submitted. (subset of new solutions)" . PHP_EOL);
            }

            // Show data
            $table = new Table();
            $table->setHeaders(['Neptun', 'Name', 'Status', 'Upload Time', 'Instructor Name']);

            $rows = [];
            /** @var StudentFile $solution */
            foreach ($newSolutions as $solution) {
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
     * Sends notification of oncoming task deadlines to concerned students
     * @param $daysToDeadline int interval in days from now to check. Default 5.
     * @return int
     */
    public function actionDigestOncomingTaskDeadlines($daysToDeadline = 5)
    {
        $sendEmails = true;
        if ($this->interactive) {
            $sendEmails = $this->promptBoolean('Send digest email notifications now?');
        }
        if (!$sendEmails) {
            $this->stdout("Email sending turned off" . PHP_EOL);
        }

        $digester = new DueSubmissionDigester($daysToDeadline, $sendEmails);
        $result = $digester->digestOncomingTaskDeadlines();

        if (!$sendEmails) {
            $this->stdout("The following student(s) has oncoming deadline(s):" .  PHP_EOL, Console::FG_GREEN);
            foreach ($result as $neptun => $mailContent) {
                $count = count($mailContent['data']);
                $this->stdout("Student $neptun has $count oncoming deadline(s)." .  PHP_EOL, Console::FG_GREEN);
            }
        }
        return ExitCode::OK;
    }
}
