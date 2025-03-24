<?php

namespace app\modules\student\helpers;

use app\models\AccessToken;
use app\models\QuizTestInstance;
use app\models\Submission;
use app\models\Subscription;
use app\models\Task;
use app\models\TaskAccessTokens;
use Yii;
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
     * @param Submission $file
     * @throws ForbiddenHttpException
     */
    public static function isItMySubmission($file)
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

    /**
     * Verify whether the user has access to a password protected task.
     * @param Task $task
     * @return void
     * @throws ForbiddenHttpException
     */
    public static function checkIfTaskUnlocked(Task $task)
    {
        if (!$task->entryPasswordUnlocked) {
            throw new ForbiddenHttpException(Yii::t('app',
                                                    'This task is password protected, unlock it with the password first!'));
        }
    }

    /**
     * Check if password has been entered for current session in password protected quiz.
     * @param QuizTestInstance $testInstance
     * @throws ForbiddenHttpException
     */
    public static function checkTestUnlocked(QuizTestInstance $testInstance)
    {
        if (!$testInstance->isUnlocked) {
            throw new ForbiddenHttpException(Yii::t('app', 'Password for exam has not been entered for this session'));
        }
    }

    /**
     * Check if exam can be started.
     * @param QuizTestInstance $testInstance
     * @throws ForbiddenHttpException
     */
    public static function canStartTest(QuizTestInstance $testInstance)
    {
        if (
            $testInstance->submitted || strtotime($testInstance->test->availablefrom) > time()
            || strtotime($testInstance->test->availableuntil) < time() || $testInstance->userID != Yii::$app->user->id
        ) {
            throw new ForbiddenHttpException(Yii::t('app', "You don't have permission to access this test instance"));
        }
    }

    /**
     * Check if exam can be finished.
     * @param QuizTestInstance $testInstance
     * @throws ForbiddenHttpException
     */
    public static function canFinishTest(QuizTestInstance $testInstance)
    {
        if (
            $testInstance->submitted || strtotime($testInstance->test->availablefrom) > time()
            || strtotime($testInstance->test->availableuntil) + 30 < time(
            ) || $testInstance->userID != Yii::$app->user->id
        ) { // 30 seconds gratis time, so JavaScript-based auto-submission at the end of the test is still valid
            throw new ForbiddenHttpException(Yii::t('app', "You don't have permission to access this test instance"));
        }
    }
}
