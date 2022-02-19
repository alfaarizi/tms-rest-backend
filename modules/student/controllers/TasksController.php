<?php

namespace app\modules\student\controllers;

use app\modules\student\helpers\PermissionHelpers;
use app\modules\student\resources\GroupResource;
use app\modules\student\resources\TaskResource;
use Yii;
use yii\data\ActiveDataProvider;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

/**
 * This class provides access to task actions for students
 */
class TasksController extends BaseStudentRestController
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
     * List tasks for the given group
     * @param int $groupID
     * @return array
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     * @OA\Get(
     *     path="/student/tasks",
     *     operationId="student::TasksFilesController::actionIndex",
     *     tags={"Student Tasks"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="groupID",
     *         in="query",
     *         required=true,
     *         description="ID of the task",
     *         explode=true,
     *         @OA\Schema(ref="#/components/schemas/int_id")
     *     ),
     *     @OA\Parameter(ref="#/components/parameters/yii2_fields"),
     *     @OA\Parameter(ref="#/components/parameters/yii2_expand"),
     *     @OA\Parameter(ref="#/components/parameters/yii2_sort"),
     *     @OA\Response(
     *         response=200,
     *         description="successful operation",
     *         @OA\JsonContent(
     *           type="array",
     *           @OA\Items(type="array", @OA\Items(ref="#/components/schemas/Student_TaskResource_Read"))
     *         ),
     *     ),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=403, ref="#/components/responses/403"),
     *    @OA\Response(response=404, ref="#/components/responses/404"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * )
     */
    public function actionIndex($groupID)
    {
        $group = GroupResource::findOne($groupID);

        if (is_null($group)) {
            throw new NotFoundHttpException(Yii::t("app", "Group not found."));
        }

        PermissionHelpers::isMyGroup($groupID);

        $categories = TaskResource::listCategories($group);

        $dataProviders = [];
        foreach ($categories as $category) {
            $query = TaskResource::find()
                ->withStudentFilesForUser(Yii::$app->user->id)
                ->where(['groupID' => $groupID])
                ->andWhere(['category' => $category])
                ->findAvailable();

            $dataProviders[] = new ActiveDataProvider([
                'query' => $query,
                'pagination' => false
            ]);
        }

        return $dataProviders;
    }

    /**
     * View task
     * @param int $id
     * @return TaskResource|array|null
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     *
     * @OA\Get(
     *     path="/student/tasks/{id}",
     *     operationId="student::TasksFilesController::actionView",
     *     tags={"Student Tasks"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *      name="id",
     *      in="path",
     *      required=true,
     *      description="ID of the group",
     *      @OA\Schema(ref="#/components/schemas/int_id"),
     *    ),
     *    @OA\Parameter(ref="#/components/parameters/yii2_fields"),
     *    @OA\Parameter(ref="#/components/parameters/yii2_expand"),
     *    @OA\Response(
     *       response=200,
     *       description="successful operation",
     *       @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Student_TaskResource_Read")),
     *    ),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=403, ref="#/components/responses/403"),
     *    @OA\Response(response=404, ref="#/components/responses/404"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * )
     */
    public function actionView($id)
    {
        $task = TaskResource::find()
            ->withStudentFilesForUser(Yii::$app->user->id)
            ->where(['{{%tasks}}.id' => $id])
            ->one();

        if (is_null($task)) {
            throw new NotFoundHttpException(Yii::t("app", "Task not found."));
        }

        PermissionHelpers::isItMyTask($id);
        PermissionHelpers::checkIfTaskAvailable($task);

        return $task;
    }
}
