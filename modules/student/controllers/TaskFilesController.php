<?php

namespace app\modules\student\controllers;

use Yii;
use app\modules\student\resources\TaskResource;
use app\modules\student\resources\TaskFileResource;
use app\modules\student\helpers\PermissionHelpers;
use yii\data\ActiveDataProvider;
use yii\web\NotFoundHttpException;
use yii\web\ForbiddenHttpException;

/**
 * This class provides access to task file actions for students
 */
class TaskFilesController extends BaseStudentRestController
{
    /**
     * @inheritdoc
     */
    protected function verbs()
    {
        return array_merge(parent::verbs(), [
            'index' => ['GET'],
            'download' => ['GET'],
        ]);
    }

    /**
     * Lists public task files for a task
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     *
     * @OA\Get(
     *     path="/student/task-files",
     *     operationId="student::TaskFilesController::actionIndex",
     *     tags={"Student Task Files"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *        name="taskID",
     *        in="query",
     *        required=true,
     *        description="ID of the task",
     *        @OA\Schema(ref="#/components/schemas/int_id"),
     *     ),
     *     @OA\Parameter(ref="#/components/parameters/yii2_fields"),
     *     @OA\Parameter(ref="#/components/parameters/yii2_expand"),
     *     @OA\Parameter(ref="#/components/parameters/yii2_sort"),
     *
     *     @OA\Response(
     *        response=200,
     *        description="successful operation",
     *        @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Student_TaskFileResource_Read")),
     *    ),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=403, ref="#/components/responses/403"),
     *    @OA\Response(response=404, ref="#/components/responses/404"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * ),
     */
    public function actionIndex(int $taskID): ActiveDataProvider
    {
        $task = TaskResource::findOne($taskID);

        if (is_null($task)) {
            throw new NotFoundHttpException(Yii::t('app', 'Task not found.'));
        }

        PermissionHelpers::isItMyTask($taskID);
        PermissionHelpers::checkIfTaskAvailable($task);

        return new ActiveDataProvider([
            'query' => $task->getTaskFiles(),
            'pagination' => false
        ]);
    }

    /**
     * Send the requested task file to the client
     * @param int $id is the id of the file
     * @throws NotFoundHttpException
     * @throws ForbiddenHttpException;
     *
     * @OA\Get(
     *     path="/student/task-files/{id}/download",
     *     operationId="student::TaskFilesController::actionDownload",
     *     tags={"Student Task Files"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *        name="id",
     *        in="path",
     *        required=true,
     *        description="ID of the file",
     *        @OA\Schema(ref="#/components/schemas/int_id"),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="successful operation",
     *     ),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=403, ref="#/components/responses/403"),
     *    @OA\Response(response=404, ref="#/components/responses/404"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * ),
     */
    public function actionDownload(int $id): void
    {
        $file = TaskFileResource::findOne($id);

        if (is_null($file)) {
            throw new NotFoundHttpException(Yii::t('app', 'Task File not found.'));
        }

        PermissionHelpers::isItMyTask($file->taskID);
        PermissionHelpers::checkIfTaskAvailable($file->task);

        if (!$file->isAttachment) {
            throw new ForbiddenHttpException(Yii::t('app', 'Task File not available.'));
        }

        if (!$file->task->entryPasswordUnlocked) {
            throw new ForbiddenHttpException(Yii::t('app', 'This task is password protected, unlock it with the password first!'));
        }

        Yii::$app->response->sendFile($file->path, basename($file->path));
    }
}
