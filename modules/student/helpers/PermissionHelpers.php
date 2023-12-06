<?php

namespace app\modules\student\helpers;

use app\models\StudentFile;
use app\models\Subscription;
use app\models\Task;
use Yii;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;

class PermissionHelpers
{
    /**
     * Verify that the task is accessible by this student.
     * @param int $id is the id of the task.
     * @throws ForbiddenHttpException
     */
    public static function isItMyTask($id)
    {
        /** @var Subscription[] $subscriptions */
        $subscriptions = Subscription::find()->select('groupID')->where(['userID' => Yii::$app->user->id])->all();
        $myGroups = array_map(
            function ($o) {
                return $o->groupID;
            },
            $subscriptions
        );

        $rightCheck = Task::find()->where(
            [
                'id' => $id,
                'groupID' => $myGroups
            ]
        )->exists();

        if (!$rightCheck) {
            throw new ForbiddenHttpException(Yii::t('app', 'You are not allowed to access this task!'));
        }
    }

    /**
     * Verify that the student file is accessible by this student.
     * @param StudentFile $file
     * @throws ForbiddenHttpException
     */
    public static function isItMyStudentFile($file)
    {
        if ($file->uploaderID != Yii::$app->user->id) {
            throw new ForbiddenHttpException(Yii::t('app', 'You are not allowed to see this document!'));
        }
    }

    /**
     * Verify that the group is accessible by this student.
     * @param int $id
     * @throws ForbiddenHttpException
     */
    public static function isMyGroup($id)
    {
        $userID = Yii::$app->user->id;

        $exists = Subscription::find()
            ->where(
                [
                    'userID' => $userID,
                    'groupID' => $id
                ]
            )->exists();

        if (!$exists) {
            throw new ForbiddenHttpException(Yii::t('app', 'You are not allowed to access this group!'));
        }
    }

    /**
     * Verify that the tasks is available.
     * @param Task $task
     * @throws ForbiddenHttpException
     */
    public static function checkIfTaskAvailable($task)
    {
        if (!empty($task->available) && strtotime($task->available) > time()) {
            throw new ForbiddenHttpException(Yii::t('app', 'This task is not available yet!'));
        }
    }
}
