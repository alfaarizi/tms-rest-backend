<?php

namespace app\components;

use app\models\StudentFile;
use app\models\Task;
use app\models\User;
use Yii;
use yii\helpers\Console;

class DueSubmissionDigester
{
    private bool $sendMails;
    private int $daysToDeadline;
    private Console $console;

    /**
     * @param int $daysToDeadline time range in days to query
     * @param bool $sendMails whether mails to be sent
     */
    public function __construct(int $daysToDeadline, bool $sendMails)
    {
        $this->daysToDeadline = $daysToDeadline;
        $this->sendMails = $sendMails;
        $this->console = new Console();
    }

    /**
     * Process due deadlines and send notifications.
     * @return array mail data returned
     */
    public function digestOncomingTaskDeadlines()
    {
        $queryResults = $this->fetchDueDeadlines();

        if (empty($queryResults)) {
            Yii::debug("No due deadlines in the next $this->daysToDeadline days.");
            return [];
        }

        $mailData = $this->transformMailData($queryResults);

        if (!$this->sendMails) {
            return $mailData;
        }

        $messages = [];
        $origLanguage = Yii::$app->language;
        foreach ($mailData as $neptun => $data) {
            if (empty($data['user']->notificationEmail)) {
                $this->console->stdout("Skipping mail sending for $neptun due to notifications turned off" . PHP_EOL);
                continue;
            }

            $this->console->stdout("Send notification mail to student $neptun" . PHP_EOL);
            Yii::$app->language = $data['user']->locale;
            $messages[] = Yii::$app->mailer->compose('student/digestOncomingDeadlines', [
                'data' => $data['data'],
                'daysToDeadline' => $this->daysToDeadline
            ])
                ->setFrom(Yii::$app->params['systemEmail'])
                ->setTo($data['user']->notificationEmail)
                ->setSubject(Yii::t('app/mail', 'Oncoming submission deadlines'));
        }
        Yii::$app->language = $origLanguage;

        // Send mass email notifications
        $mailSent = Yii::$app->mailer->sendMultiple($messages);
        $this->console->stdout("$mailSent number of notification mails sent" . PHP_EOL);
        return $mailData;
    }

    /**
     * @return Task[]
     */
    private function fetchDueDeadlines(): array
    {
        return Task::find()
            ->oncomingDeadline($this->daysToDeadline)
            ->withStudents(true)
            ->joinWith('studentFiles')
            ->all();
    }

    /**
     * Groups due tasks by student for each student and filters tasks that have been submitted successfully.
     *
     * Input structure: tasks with due deadlines (task->group->subscription->user)
     * Output structure: student->array of (due task, student file of task)
     *
     * @param Task[] $tasks tasks with due deadlines (task->group->subscription->user)
     * @return array<string, array{'user': User, 'data': array<int, array{'task': Task, 'studentFile': StudentFile|null}>}> The outermost array is keyed by Neptun code
     */
    private function transformMailData(array $tasks): array
    {
        $result = [];
        foreach ($tasks as $task) {
            foreach ($task->group->subscriptions as $subscription) {
                $student = $subscription->user;
                $submission = array_filter($task->studentFiles, function ($studentFile) use ($student) {
                    return $studentFile->uploaderID == $student->id;
                });
                $idx = array_key_first($submission);
                $submission = empty($submission) ? null : $submission[$idx];
                if (empty($submission) || in_array($submission->isAccepted, ['Failed', 'Rejected'])) {
                    if (!array_key_exists($student->neptun, $result)) {
                        $result[$student->neptun] = [
                            'user' => $student,
                            'data' => [],
                        ];
                    }
                    $result[$student->neptun]['data'][] = [
                        'task' => $task,
                        'studentFile' => $submission
                    ];
                }
            }
        }
        return $result;
    }
}
