<?php

namespace app\modules\admin\controllers;

use app\modules\admin\resources\NotificationResource;
use yii\data\ActiveDataProvider;
use Yii;
use yii\web\ServerErrorHttpException;
use yii\web\NotFoundHttpException;

/**
 * @OA\PathItem(
 *   path="/admin/notifications/{id}",
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
 * Controller class for managing notifications
 */
class NotificationsController extends BaseAdminActiveController
{
    public $modelClass = NotificationResource::class;


    /**
     * @inheritdoc
     */
    public function actions(): array
    {
        $actions = parent::actions();
        $actions['index']['prepareDataProvider'] = [$this, 'prepareDataProvider'];
        unset($actions['create']);
        unset($actions['update']);
        return $actions;
    }

    public function prepareDataProvider(): ActiveDataProvider
    {
        return new ActiveDataProvider(
            [
                'query' => $this->modelClass::find(),
                'pagination' => false,
                'sort' => false,
            ]
        );
    }

    /**
     * @OA\Post(
     *       path="/admin/notifications",
     *       operationId="admin::NotificationsController::actionCreate",
     *       summary="Create a new notification",
     *       tags={"Admin Notifications"},
     *       security={{"bearerAuth":{}}},
     *       @OA\Parameter(ref="#/components/parameters/yii2_fields"),
     *       @OA\Parameter(ref="#/components/parameters/yii2_expand"),
     *       @OA\RequestBody(
     *           description="new notification",
     *           @OA\MediaType(
     *               mediaType="application/json",
     *               @OA\Schema(ref="#/components/schemas/Admin_NotificationResource_ScenarioDefault"),
     *           )
     *       ),
     *       @OA\Response(
     *           response=201,
     *           description="new notification created",
     *           @OA\JsonContent(ref="#/components/schemas/Admin_NotificationResource_Read"),
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

        if (!$notification->validate()) {
            $this->response->statusCode = 422;
            return $notification->errors;
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
     * @OA\Put(
     *       path="/admin/notifications/{id}",
     *       operationId="admin::NotificationsController::actionUpdate",
     *       summary="Update a notification",
     *       tags={"Admin Notifications"},
     *       security={{"bearerAuth":{}}},
     *       @OA\Parameter(ref="#/components/parameters/yii2_fields"),
     *       @OA\Parameter(ref="#/components/parameters/yii2_expand"),
     *       @OA\RequestBody(
     *           description="updated notification",
     *           @OA\MediaType(
     *               mediaType="application/json",
     *               @OA\Schema(ref="#/components/schemas/Admin_NotificationResource_ScenarioDefault"),
     *           )
     *       ),
     *       @OA\Response(
     *           response=200,
     *           description="notification updated",
     *           @OA\JsonContent(ref="#/components/schemas/Admin_NotificationResource_Read"),
     *       ),
     *      @OA\Response(response=401, ref="#/components/responses/401"),
     *      @OA\Response(response=404, ref="#/components/responses/404"),
     *      @OA\Response(response=422, ref="#/components/responses/422"),
     *      @OA\Response(response=500, ref="#/components/responses/500"),
     *   ),
     */
    public function actionUpdate(int $id)
    {
        $notification = NotificationResource::findOne($id);

        if (is_null($notification)) {
            throw new NotFoundHttpException(Yii::t('app', 'Notification not found.'));
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
     * Annotate ActiveController actions
     *
     * @OA\Get(
     *      path="/admin/notifications",
     *      operationId="admin::NotificationsController::actionIndex",
     *      summary="List notifications",
     *      tags={"Admin Notifications"},
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(ref="#/components/parameters/yii2_fields"),
     *      @OA\Parameter(ref="#/components/parameters/yii2_expand"),
     *      @OA\Parameter(ref="#/components/parameters/yii2_sort"),
     *
     *      @OA\Response(
     *         response=200,
     *         description="successful operation",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Admin_NotificationResource_Read")),
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/401"),
     *     @OA\Response(response=500, ref="#/components/responses/500"),
     *  ),
     *
     * @OA\Get(
     *       path="/admin/notifications/{id}",
     *       operationId="admin::NotificationsController::actionView",
     *       summary="View notification",
     *       tags={"Admin Notifications"},
     *       security={{"bearerAuth":{}}},
     *       @OA\Parameter(ref="#/components/parameters/yii2_fields"),
     *       @OA\Parameter(ref="#/components/parameters/yii2_expand"),
     *       @OA\Parameter(ref="#/components/parameters/yii2_sort"),
     *
     *       @OA\Response(
     *          response=200,
     *          description="successful operation",
     *          @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Admin_NotificationResource_Read")),
     *      ),
     *      @OA\Response(response=401, ref="#/components/responses/401"),
     *      @OA\Response(response=404, ref="#/components/responses/404"),
     *      @OA\Response(response=500, ref="#/components/responses/500"),
     *   ),
     *
     * @OA\Delete(
     *        path="/admin/notifications/{id}",
     *        operationId="admin::NotificationsController::actionDeleteNotification",
     *        tags={"Admin Notifications"},
     *        security={{"bearerAuth":{}}},
     *        summary="Delete a notification",
     *       @OA\Response(
     *            response=204,
     *            description="notification deleted",
     *        ),
     *       @OA\Response(response=401, ref="#/components/responses/401"),
     *       @OA\Response(response=404, ref="#/components/responses/404"),
     *       @OA\Response(response=500, ref="#/components/responses/500"),
     *    ),
     */
}
