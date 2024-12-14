<?php

namespace app\modules\instructor\controllers;

use app\models\Notification;
use app\modules\instructor\resources\GroupResource;
use app\modules\instructor\resources\NotificationResource;
use yii\data\ActiveDataProvider;
use Yii;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\ServerErrorHttpException;
use yii\web\NotFoundHttpException;

/**
 * @OA\PathItem(
 *   path="/instructor/notifications/{id}",
 *   @OA\Parameter(
 *      name="id",
 *      in="path",
 *      required=true,
 *      description="ID of the notification",
 *      @OA\Schema(ref="#/components/schemas/int_id"),
 *   ),
 * )
 */

/**
 * Controller class for managing group level notifications
 */
class NotificationsController extends BaseInstructorRestController
{
    /**
     * @inheritdoc
     */
    protected function verbs(): array
    {
        return array_merge(
            parent::verbs(),
            [
                'index' => ['GET'],
                'view' => ['GET'],
                'create' => ['POST'],
                'delete' => ['DELETE'],
                'update' => ['PATCH', 'PUT']
            ]
        );
    }

    /**
     * List notifications for the given group
     * @return ActiveDataProvider[]
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     *
     * @OA\Get(
     *     path="/instructor/notifications",
     *     operationId="instructor::NotificationsController::actionIndex",
     *     tags={"Instructor Notifications"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="groupID",
     *         in="query",
     *         required=true,
     *         description="ID of the group",
     *         explode=true,
     *         @OA\Schema(ref="#/components/schemas/int_id")
     *     ),
     *     @OA\Parameter(ref="#/components/parameters/yii2_fields"),
     *     @OA\Parameter(ref="#/components/parameters/yii2_expand"),
     *     @OA\Parameter(ref="#/components/parameters/yii2_sort"),
     *
     *     @OA\Response(
     *        response=200,
     *        description="successful operation",
     *        @OA\JsonContent(
     *          type="array",
     *          @OA\Items(ref="#/components/schemas/Instructor_NotificationResource_Read")
     *        ),
     *    ),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=403, ref="#/components/responses/403"),
     *    @OA\Response(response=404, ref="#/components/responses/404"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * )
     */
    public function actionIndex(int $groupID): ActiveDataProvider
    {
        $group = GroupResource::findOne($groupID);

        if (is_null($group)) {
            throw new NotFoundHttpException('Group not found');
        }

        // Authorization check
        if (!Yii::$app->user->can('manageGroup', ['groupID' => $groupID])) {
            throw new ForbiddenHttpException(
                Yii::t('app', 'You must be an instructor of the group to perform this action!')
            );
        }

        $query = NotificationResource::find()
            -> andWhere(['groupID' => $groupID]);

        return new ActiveDataProvider(
            [
                'query' => $query,
                'pagination' => false
            ]
        );
    }

    /**
     * View Notification
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     * @throws BadRequestHttpException
     *
     * @OA\Get(
     *     path="/instructor/notifications/{id}",
     *     operationId="instructor::NotificationsController::actionView",
     *     tags={"Instructor Notifications"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(ref="#/components/parameters/yii2_fields"),
     *     @OA\Parameter(ref="#/components/parameters/yii2_expand"),
     *     @OA\Response(
     *         response=200,
     *         description="successful operation",
     *         @OA\JsonContent(ref="#/components/schemas/Instructor_NotificationResource_Read"),
     *     ),
     *    @OA\Response(response=400, ref="#/components/responses/400"),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=403, ref="#/components/responses/403"),
     *    @OA\Response(response=404, ref="#/components/responses/404"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * )
     */
    public function actionView(int $id): NotificationResource
    {
        $notification = NotificationResource::findOne($id);

        if (is_null($notification)) {
            throw new NotFoundHttpException(Yii::t('app', 'Notification not found.'));
        }

        if ($notification->scope != Notification::SCOPE_GROUP) {
            throw new BadRequestHttpException(Yii::t('app', 'Notification is not a group notification.'));
        }

        // Authorization check
        if (!Yii::$app->user->can('manageGroup', ['groupID' => $notification->groupID])) {
            throw new ForbiddenHttpException(
                Yii::t('app', 'You must be an instructor of the group to perform this action!')
            );
        }

        return $notification;
    }

    /**
     * @OA\Post(
     *       path="/instructor/notifications",
     *       operationId="instructor::NotificationsController::actionCreate",
     *       summary="Create a new notification",
     *       tags={"Instructor Notifications"},
     *       security={{"bearerAuth":{}}},
     *       @OA\Parameter(ref="#/components/parameters/yii2_fields"),
     *       @OA\Parameter(ref="#/components/parameters/yii2_expand"),
     *       @OA\RequestBody(
     *           description="new notification",
     *           @OA\MediaType(
     *               mediaType="application/json",
     *               @OA\Schema(ref="#/components/schemas/Instructor_NotificationResource_ScenarioDefault"),
     *           )
     *       ),
     *       @OA\Response(
     *           response=201,
     *           description="new notification created",
     *           @OA\JsonContent(ref="#/components/schemas/Instructor_NotificationResource_Read"),
     *       ),
     *      @OA\Response(response=401, ref="#/components/responses/401"),
     *      @OA\Response(response=422, ref="#/components/responses/422"),
     *      @OA\Response(response=500, ref="#/components/responses/500"),
     *   ),
     */
    public function actionCreate()
    {
        $notification = new NotificationResource();
        $notification->load(Yii::$app->request->post(), '');
        $notification->scope = Notification::SCOPE_GROUP;

        if (!$notification->validate()) {
            $this->response->statusCode = 422;
            return $notification->errors;
        }

        // Authorization check
        if (!Yii::$app->user->can('manageGroup', ['groupID' => $notification->groupID])) {
            throw new ForbiddenHttpException(
                Yii::t('app', 'You must be an instructor of the group to perform this action!')
            );
        }

        if ($notification->save(false)) {
            Yii::info(
                "A new notification has been created: $notification->id.",
                __METHOD__
            );
            $this->response->statusCode = 201;
            return NotificationResource::findOne($notification->id);
        } else {
            throw new ServerErrorHttpException(Yii::t('app', 'A database error occurred'));
        }
    }

    /**
     * Update notification.
     * @throws NotFoundHttpException
     * @throws BadRequestHttpException
     * @throws ForbiddenHttpException
     * @throws ServerErrorHttpException
     * @OA\Put(
     *       path="/instructor/notifications/{id}",
     *       operationId="instructor::NotificationsController::actionUpdate",
     *       summary="Update a notification",
     *       tags={"Instructor Notifications"},
     *       security={{"bearerAuth":{}}},
     *       @OA\Parameter(ref="#/components/parameters/yii2_fields"),
     *       @OA\Parameter(ref="#/components/parameters/yii2_expand"),
     *       @OA\RequestBody(
     *           description="updated notification",
     *           @OA\MediaType(
     *               mediaType="application/json",
     *               @OA\Schema(ref="#/components/schemas/Instructor_NotificationResource_ScenarioUpdate"),
     *           )
     *       ),
     *       @OA\Response(
     *           response=200,
     *           description="notification updated",
     *           @OA\JsonContent(ref="#/components/schemas/Instructor_NotificationResource_Read"),
     *       ),
     *      @OA\Response(response=400, ref="#/components/responses/400"),
     *      @OA\Response(response=401, ref="#/components/responses/401"),
     *      @OA\Response(response=404, ref="#/components/responses/404"),
     *      @OA\Response(response=422, ref="#/components/responses/422"),
     *      @OA\Response(response=500, ref="#/components/responses/500"),
     *   ),
     */
    public function actionUpdate(int $id)
    {
        $notification = NotificationResource::findOne($id);
        $notification->scenario = NotificationResource::SCENARIO_UPDATE;

        if (is_null($notification)) {
            throw new NotFoundHttpException(Yii::t('app', 'Notification not found.'));
        }

        if ($notification->scope != Notification::SCOPE_GROUP) {
            throw new BadRequestHttpException(Yii::t('app', 'Notification is not a group notification.'));
        }

        // Authorization check
        if (!Yii::$app->user->can('manageGroup', ['groupID' => $notification->groupID])) {
            throw new ForbiddenHttpException(
                Yii::t('app', 'You must be an instructor of the group to perform this action!')
            );
        }

        $notification->load(Yii::$app->request->post(), '');

        if ($notification->save()) {
            return NotificationResource::findOne($notification->id);
        } elseif ($notification->hasErrors()) {
            $this->response->statusCode = 422;
            return $notification->errors;
        } else {
            throw new ServerErrorHttpException(Yii::t('app', 'A database error occurred'));
        }
    }

    /**
     * Remove a notification
     * @throws NotFoundHttpException
     * @throws BadRequestHttpException
     * @throws ServerErrorHttpException
     * @throws ForbiddenHttpException
     * @throws \Throwable
     *
     * @OA\Delete(
     *     path="/instructor/notifications/{id}",
     *     operationId="instructor::NotificationsController::actionDelete",
     *     tags={"Instructor Notifications"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=204,
     *         description="notification deleted",
     *     ),
     *    @OA\Response(response=400, ref="#/components/responses/400"),
     *    @OA\Response(response=403, ref="#/components/responses/403"),
     *    @OA\Response(response=404, ref="#/components/responses/404"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * )
     */
    public function actionDelete(int $id): void
    {
        // Fetch the entities.
        $notification = NotificationResource::findOne($id);

        if (is_null($notification)) {
            throw new NotFoundHttpException(Yii::t('app', 'Notification not found.'));
        }

        if ($notification->scope != Notification::SCOPE_GROUP) {
            throw new BadRequestHttpException(Yii::t('app', 'Notification is not a group notification.'));
        }

        // Authorization check
        if (!Yii::$app->user->can('manageGroup', ['groupID' => $notification->groupID])) {
            throw new ForbiddenHttpException(
                Yii::t('app', 'You must be an instructor of the group to perform this action!')
            );
        }

        if ($notification->delete()) {
            $this->response->statusCode = 204;
        } else {
            throw new ServerErrorHttpException(
                Yii::t('app', 'Failed to delete notification. Message: ') . Yii::t('app', "Database errors")
            );
        }
    }
}
