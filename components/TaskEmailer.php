<?php

namespace app\components;

use app\models\Group;
use app\models\Task;
use Yii;

/**
 * Manages email notification of tasks.
 */
class TaskEmailer
{
    private Task $task;

    public function __construct(Task $task)
    {
        $this->task = $task;
    }

    public function sendCreatedNotification(bool $force = false)
    {
        if ($this->task->sentCreatedEmail && !$force) {
            throw new \yii\base\Exception('Email notifications already sent for this task.');
        }

        // Email notifications
        $messages = [];

        $originalLanguage = Yii::$app->language;
        foreach ($this->task->group->subscriptions as $subscription) {
            if (!empty($subscription->user->notificationEmail)) {
                Yii::$app->language = $subscription->user->locale;
                $messages[] = Yii::$app->mailer->compose(
                    'student/newTask',
                    [
                        'task' => $this->task,
                        'actor' => Yii::$app->user->identity,
                    ]
                )
                    ->setFrom(Yii::$app->params['systemEmail'])
                    ->setTo($subscription->user->notificationEmail)
                    ->setSubject(Yii::t('app/mail', 'New task'));
            }
        }
        Yii::$app->language = $originalLanguage;

        // Send mass email notifications
        Yii::$app->mailer->sendMultiple($messages);

        $this->task->sentCreatedEmail = true;
        $this->task->save();
    }

    public function sendDeadlineChangeNotification()
    {
        // Email notifications
        $messages = [];
        $group = Group::findOne($this->task->groupID);

        $originalLanguage = Yii::$app->language;
        foreach ($this->task->group->subscriptions as $subscription) {
            if (!empty($subscription->user->notificationEmail)) {
                Yii::$app->language = $subscription->user->locale;
                $messages[] = Yii::$app->mailer->compose(
                    'student/updateTaskDeadline',
                    [
                        'task' => $this->task,
                        'actor' => Yii::$app->user->identity,
                        'group' => $group,
                    ]
                )
                    ->setFrom(Yii::$app->params['systemEmail'])
                    ->setTo($subscription->user->notificationEmail)
                    ->setSubject(Yii::t('app/mail', 'Task deadline change'));
            }
        }
        Yii::$app->language = $originalLanguage;

        // Send mass email notifications
        Yii::$app->mailer->sendMultiple($messages);
    }
}
