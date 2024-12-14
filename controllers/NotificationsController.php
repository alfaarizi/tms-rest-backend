<?php

namespace app\controllers;

use app\models\Notification;
use app\models\NotificationUser;
use app\resources\NotificationResource;
use yii\data\ActiveDataProvider;
use yii\helpers\ArrayHelper;
use Yii;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;

/**
 * This class controls the notification actions
 */
class NotificationsController extends BaseRestController
{
    /**
     * @inheritdoc
     */
    public function behaviors(): array
    {
        $behaviors = parent::behaviors();
        $behaviors['authenticator']['optional'] = ['index'];

        return $behaviors;
    }

    /**
     * @inheritdoc
     */
    protected function verbs(): array
    {
        return ArrayHelper::merge(
            parent::verbs(),
            [
                'index' => ['GET'],
                'dismiss' => ['POST']
            ]
        );
    }

    /**
     * List notifications
     * @OA\Get(
     *     path="/common/notifications",
     *     tags={"Common Notifications"},
     *     security={{"bearerAuth":{}}},
     *     operationId="common::NotificationsController::actionIndex",
     *
     *     @OA\Parameter(ref="#/components/parameters/yii2_fields"),
     *     @OA\Parameter(ref="#/components/parameters/yii2_expand"),
     *     @OA\Parameter(ref="#/components/parameters/yii2_sort"),
     *
     *     @OA\Response(
     *         response=200,
     *         description="successful operation",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Common_NotificationResource_Read")),
     *     ),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * ),
     */
    public function actionIndex(): ActiveDataProvider
    {
        if (Yii::$app->user->isGuest) {
            // User is a guest, find notifications with scope everyone
            $query = NotificationResource::find()
                ->andWhere(['scope' => Notification::SCOPE_EVERYONE])
                ->notGroupNotification()
                ->findAvailable();

            return new ActiveDataProvider([
                'query' => $query,
                'pagination' => false,
            ]);
        }

        /** @var \app\models\User $user */
        $user = Yii::$app->user->identity;

        // Fetch notifications for logged in users
        $query = NotificationResource::find()
            ->where(['in', 'scope', [Notification::SCOPE_EVERYONE, Notification::SCOPE_USER]])
            ->notGroupNotification()
            ->notDismissedBy($user->id)
            ->findAvailable();

        // Fetch group notifications for user
        $query = $query->union(
            NotificationResource::find()
                ->groupNotification($user->id)
                ->notDismissedBy($user->id)
                ->findAvailable()
        );

        if ($user->isFaculty) {
            // User is an instructor, find notifications with scope faculty
            $query = $query->union(
                NotificationResource::find()
                    ->where(['scope' => Notification::SCOPE_FACULTY])
                    ->notDismissedBy($user->id)
                    ->findAvailable()
            );
        }

        if ($user->isStudent) {
            // User is a student, find notifications with scope student
            $query = $query->union(
                NotificationResource::find()
                    ->where(['scope' => Notification::SCOPE_STUDENT])
                    ->notDismissedBy($user->id)
                    ->findAvailable()
            );
        }

        return new ActiveDataProvider([
            'query' => $query,
            'pagination' => false,
        ]);
    }


    /**
     * Dismiss a notification.
     *
     * @param int $notificationID the id of the notification
     * @throws ServerErrorHttpException
     * @throws NotFoundHttpException
     * @throws BadRequestHttpException
     *
     * @OA\Post(
     *     path="/common/notifications/dismiss",
     *     operationId="common::NotificationsController::actionDismiss",
     *     tags={"Common Notifications"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *            name="notificationID",
     *            in="query",
     *            required=true,
     *            description="ID of the notification",
     *            @OA\Schema(ref="#/components/schemas/int_id"),
     *      ),
     *     @OA\Parameter(ref="#/components/parameters/yii2_fields"),
     *     @OA\Parameter(ref="#/components/parameters/yii2_expand"),
     *     @OA\Response(
     *         response=200,
     *         description="notification dismissed",
     *         @OA\JsonContent(ref="#/components/schemas/Common_NotificationResource_Read"),
     *     ),*
     *     @OA\Response(response=401, ref="#/components/responses/401"),
     *     @OA\Response(response=400, ref="#/components/responses/400"),
     *     @OA\Response(response=404, ref="#/components/responses/404"),
     *     @OA\Response(response=500, ref="#/components/responses/500"),
     * )
     */
    public function actionDismiss(int $notificationID): NotificationResource
    {
        $notification = NotificationResource::findOne($notificationID);
        if (is_null($notification)) {
            throw new NotFoundHttpException(Yii::t('app', 'Notification not found.'));
        }

        if (!$notification->dismissible) {
            throw new BadRequestHttpException(Yii::t('app', 'Notification is not dismissible.'));
        }

        $userID = Yii::$app->user->id;
        if (is_null(NotificationUser::findOne(['userID' => $userID, 'notificationID' => $notificationID]))) {
            $notificationUser = new NotificationUser(
                [
                   'userID' => $userID,
                   'notificationID' => $notificationID
                ]
            );
            if (!$notificationUser->save()) {
                throw new ServerErrorHttpException(Yii::t('app', 'Failed to dismiss notification.'));
            }
        }
        return $notification;
    }
}
