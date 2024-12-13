<?php

namespace app\modules\student\controllers;

use app\models\AccessToken;
use app\models\TaskAccessTokens;
use app\modules\student\helpers\PermissionHelpers;
use app\modules\student\resources\GroupResource;
use app\modules\student\resources\TaskResource;
use app\modules\student\resources\UnlockTaskResource;
use Yii;
use yii\data\ActiveDataProvider;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;

/**
 * This class provides access to task actions for students
 */
class TasksController extends BaseSubmissionsController
{
    /**
     * @inheritdoc
     */
    protected function verbs()
    {
        return array_merge(parent::verbs(), [
            'index' => ['GET'],
            'view' => ['GET'],
            'unlock' => ['POST'],
        ]);
    }

    /**
     * List tasks for the given group
     * @return ActiveDataProvider[]
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
    public function actionIndex(int $groupID): array
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
                ->withSubmissionsForUser(Yii::$app->user->id)
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
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
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
    public function actionView(int $id): TaskResource
    {
        $task = TaskResource::find()
            ->withSubmissionsForUser(Yii::$app->user->id)
            ->where(['{{%tasks}}.id' => $id])
            ->one();

        if (is_null($task)) {
            throw new NotFoundHttpException(Yii::t("app", "Task not found."));
        }

        PermissionHelpers::isItMyTask($id);
        PermissionHelpers::checkIfTaskAvailable($task);

        return $task;
    }


    /**
     * Unlock a task
     * @OA\Post(
     *     path="/student/tasks/{id}/unlock",
     *     operationId="student::TasksFilesController::actionUnlock",
     *     tags={"Student Tasks"},
     *     security={{"bearerAuth":{}}},
     *
     *    @OA\Parameter(
     *       name="id",
     *       in="path",
     *       required=true,
     *       description="ID of the task",
     *       @OA\Schema(ref="#/components/schemas/int_id"),
     *    ),
     *    @OA\Parameter(ref="#/components/parameters/yii2_fields"),
     *    @OA\Parameter(ref="#/components/parameters/yii2_expand"),
     *    @OA\RequestBody(
     *          description="Task id and password to unlock the task",
     *          @OA\MediaType(
     *              mediaType="multipart/form-data",
     *              @OA\Schema(ref="#/components/schemas/Student_UnlockTaskResource_ScenarioDefault"),
     *          )
     *      ),
     *    @OA\Response(
     *        response=200,
     *        description="successful operation",
     *        @OA\JsonContent(ref="#/components/schemas/Student_TaskResource_Read"),
     *     ),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=403, ref="#/components/responses/403"),
     *    @OA\Response(response=404, ref="#/components/responses/404"),
     *    @OA\Response(response=422, ref="#/components/responses/422"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * )
     * @throws NotFoundHttpException
     * @throws ForbiddenHttpException
     * @throws ServerErrorHttpException
     */
    public function actionUnlock(int $id) {
        $unlockTaskResource = new UnlockTaskResource();
        $unlockTaskResource->load(Yii::$app->request->post(), '');

        if (!$unlockTaskResource->validate()) {
            $this->response->statusCode = 422;
            return $unlockTaskResource->errors;
        }

        $task = TaskResource::findOne($id);

        if (is_null($task)) {
            throw new NotFoundHttpException(Yii::t('app', 'Task not found.'));
        }

        PermissionHelpers::isItMyTask($task->id);

        if ($task->entryPassword !== $unlockTaskResource->password) {
            $unlockTaskResource->addError('password', Yii::t('app', 'Invalid password'));
        }

        if ($unlockTaskResource->hasErrors()) {
            $this->response->statusCode = 422;
            return $unlockTaskResource->errors;
        }

        $taskAccessToken = new TaskAccessTokens();
        $taskAccessToken->accessToken = AccessToken::getCurrent()->token;
        $taskAccessToken->taskId = $task->id;

        if (!$taskAccessToken->validate()) {
            $this->response->statusCode = 422;
            return $taskAccessToken->errors;
        }

        if (!$taskAccessToken->save(false)) {
            throw new ServerErrorHttpException(
                Yii::t('app', 'Failed to save access token. Message: ') . Yii::t('app', 'A database error occurred')
            );
        } else {
            Yii::info(
                "Task has been unlocked: {$task->name} ($task->id)",
                __METHOD__
            );
        }

        return $task;
    }
}
