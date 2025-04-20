<?php

namespace app\modules\admin\controllers;

use app\models\IpRestriction;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;

/**
 * @OA\PathItem(
 *     path="/admin/ip-restriction"
 * )
 * @OA\PathItem(
 *     path="/admin/ip-restriction/{id}",
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the IP restriction",
 *         @OA\Schema(ref="#/components/schemas/int_id"),
 *     )
 * )
 *
 * Controller class for managing IP restrictions.
 */
/**
 * @OA\Schema(
 *     schema="IpRestriction",
 *     type="object",
 *     title="Ip Restriction",
 *     @OA\Property(
 *         property="id",
 *         type="integer",
 *         description="The ID of the IP restriction"
 *     ),
 *     @OA\Property(
 *         property="name",
 *         type="string",
 *         description="The name of the IP restriction"
 *     ),
 *     @OA\Property(
 *         property="ipAddress",
 *         type="string",
 *         description="The IP address"
 *     ),
 *     @OA\Property(
 *         property="ipMask",
 *         type="string",
 *         description="The IP mask"
 *     )
 * )
 */
class IpRestrictionController extends BaseAdminRestController
{
    /**
     * @OA\Get(
     *     path="/admin/ip-restriction",
     *     operationId="admin::IpRestriction::actionIndex",
     *     summary="List all IP restriction",
     *     tags={"Admin IP Restrictions"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="successful operation",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/IpRestriction")),
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/401"),
     *     @OA\Response(response=500, ref="#/components/responses/500"),
     * )
     */
    public function actionIndex()
    {
        return IpRestriction::find()->all();
    }

    /**
     * @OA\Get(
     *     path="/admin/ip-restriction/{id}",
     *     operationId="admin::IpRestriction::actionView",
     *     summary="View a specific IP restriction",
     *     tags={"Admin IP Restrictions"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the IP restriction",
     *         @OA\Schema(ref="#/components/schemas/int_id"),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="successful operation",
     *         @OA\JsonContent(ref="#/components/schemas/IpRestriction"),
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/401"),
     *     @OA\Response(response=404, ref="#/components/responses/404"),
     *     @OA\Response(response=500, ref="#/components/responses/500"),
     * )
     */
    public function actionView($id)
    {
        return $this->findModel($id);
    }

    /**
     * @OA\Post(
     *     path="/admin/ip-restriction",
     *     operationId="admin::IpRestriction::actionCreate",
     *     summary="Create a new IP restriction",
     *     tags={"Admin IP Restrictions"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         description="IP restriction data",
     *         @OA\JsonContent(ref="#/components/schemas/IpRestriction"),
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="IP restriction created",
     *         @OA\JsonContent(ref="#/components/schemas/IpRestriction"),
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/401"),
     *     @OA\Response(response=422, ref="#/components/responses/422"),
     *     @OA\Response(response=500, ref="#/components/responses/500"),
     * )
     */
    public function actionCreate()
    {
        $model = new IpRestriction();

        if ($model->load(\Yii::$app->request->post(), '') && $model->validate()) {
            if ($model->save()) {
                \Yii::$app->response->statusCode = 201;
                return $model;
            } else {
                throw new ServerErrorHttpException(\Yii::t('app', 'Failed to create the IP restriction due to a database error.'));
            }
        } else {
            \Yii::$app->response->statusCode = 422;
            return $model->errors;
        }
    }

    /**
     * @OA\Delete(
     *     path="/admin/ip-restriction/{id}",
     *     operationId="admin::IpRestriction::actionDelete",
     *     summary="Delete an IP restriction",
     *     tags={"Admin IP Restrictions"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the IP restriction",
     *         @OA\Schema(ref="#/components/schemas/int_id"),
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="IP restriction deleted",
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/401"),
     *     @OA\Response(response=404, ref="#/components/responses/404"),
     *     @OA\Response(response=500, ref="#/components/responses/500"),
     * )
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);

        if ($model->delete()) {
            \Yii::$app->response->statusCode = 204;
        } else {
            throw new ServerErrorHttpException(\Yii::t('app', 'Failed to delete the IP restriction due to a database error.'));
        }
    }

    /**
     * Finds the IpRestriction model based on its primary key value.
     * @param int $id
     * @return IpRestriction|null
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = IpRestriction::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException(\Yii::t('app', 'The requested IP restriction does not exist.'));
    }

        /**
     * @OA\Put(
     *     path="/admin/ip-restriction/{id}",
     *     operationId="admin::IpRestrictionController::actionUpdate",
     *     summary="Update an existing IP restriction",
     *     tags={"Admin IP Restrictions"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the IP restriction",
     *         @OA\Schema(ref="#/components/schemas/int_id"),
     *     ),
     *     @OA\RequestBody(
     *         description="IP restriction data to update",
     *         @OA\JsonContent(ref="#/components/schemas/IpRestriction"),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="IP restriction updated",
     *         @OA\JsonContent(ref="#/components/schemas/IpRestriction"),
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/401"),
     *     @OA\Response(response=404, ref="#/components/responses/404"),
     *     @OA\Response(response=422, ref="#/components/responses/422"),
     *     @OA\Response(response=500, ref="#/components/responses/500"),
     * )
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(\Yii::$app->request->post(), '') && $model->validate()) {
            if ($model->save()) {
                return $model;
            } else {
                throw new ServerErrorHttpException(\Yii::t('app', 'Failed to update the IP restriction due to a database error.'));
            }
        } else {
            \Yii::$app->response->statusCode = 422;
            return $model->errors;
        }
    }

}
