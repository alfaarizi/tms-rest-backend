<?php

namespace app\modules\student\controllers;

use app\modules\student\helpers\PermissionHelpers;
use app\modules\student\resources\GroupResource;
use Yii;
use yii\data\ActiveDataProvider;
use yii\web\NotFoundHttpException;

/**
 * This class provides access to group actions for students
 */
class GroupsController extends BaseSubmissionsController
{
    /**
     * @inheritdoc
     */
    protected function verbs()
    {
        return array_merge(parent::verbs(), [
            'index' => ['GET'],
            'view' => ['GET'],
        ]);
    }

    /**
     * Lists groups for the current student in the given semester
     *
     * @OA\Get(
     *     path="/student/groups",
     *     operationId="student::GroupsController::actionIndex",
     *     tags={"Student Groups"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *        name="semesterID",
     *        in="query",
     *        required=true,
     *        description="ID of the semester",
     *        @OA\Schema(ref="#/components/schemas/int_id"),
     *     ),
     *     @OA\Parameter(ref="#/components/parameters/yii2_fields"),
     *     @OA\Parameter(ref="#/components/parameters/yii2_expand"),
     *     @OA\Parameter(ref="#/components/parameters/yii2_sort"),
     *
     *     @OA\Response(
     *        response=200,
     *        description="successful operation",
     *        @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Student_GroupResource_Read")),
     *    ),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * ),
     */
    public function actionIndex(int $semesterID): ActiveDataProvider
    {
        $userID = Yii::$app->user->id;
        $query = GroupResource::find()
            ->findForStudent($userID, $semesterID);

        return new ActiveDataProvider([
            'query' => $query,
            'pagination' => false
        ]);
    }

    /**
     * View a group
     * @throws NotFoundHttpException
     * @throws \yii\web\ForbiddenHttpException
     *
     * @OA\Get(
     *     path="/student/groups/{id}",
     *     operationId="student::GroupsController::actionView",
     *     tags={"Student Groups"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the group",
     *         @OA\Schema(ref="#/components/schemas/int_id"),
     *     ),
     *     @OA\Parameter(ref="#/components/parameters/yii2_fields"),
     *     @OA\Parameter(ref="#/components/parameters/yii2_expand"),
     *
     *     @OA\Response(
     *         response=200,
     *         description="successful operation",
     *         @OA\JsonContent(ref="#/components/schemas/Student_GroupResource_Read"),
     *     ),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=403, ref="#/components/responses/403"),
     *    @OA\Response(response=404, ref="#/components/responses/404"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * ),
     */
    public function actionView(int $id): GroupResource
    {
        $group = GroupResource::findOne($id);
        if (is_null($group)) {
            throw new NotFoundHttpException(Yii::t("app", "Group not found."));
        }

        PermissionHelpers::isMyGroup($id);

        return $group;
    }
}
