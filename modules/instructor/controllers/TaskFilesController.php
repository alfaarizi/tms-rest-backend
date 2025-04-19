<?php

namespace app\modules\instructor\controllers;

use app\components\docker\DockerContainerBuilder;
use app\components\docker\WebTesterContainer;
use app\components\WebAssignmentTester;
use app\exceptions\AddFailedException;
use app\exceptions\DockerContainerException;
use app\models\TaskFile;
use app\models\Task;
use app\modules\instructor\resources\TaskFileResource;
use app\modules\instructor\resources\TaskFilesUploadResultResource;
use app\modules\instructor\resources\TaskResource;
use app\modules\instructor\resources\UploadFailedResource;
use app\modules\instructor\resources\UploadTaskFileResource;
use app\resources\SemesterResource;
use Yii;
use yii\data\ActiveDataProvider;
use yii\db\Exception;
use yii\db\StaleObjectException;
use yii\helpers\FileHelper;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;
use yii\web\UnprocessableEntityHttpException;
use yii\web\UploadedFile;

/**
 * This class provides access to task files for instructors
 */
class TaskFilesController extends BaseInstructorRestController
{
    /**
     * @inheritdoc
     */
    protected function verbs()
    {
        return array_merge(parent::verbs(), [
            'index' => ['GET'],
            'download' => ['GET'],
            'create' => ['POST'],
            'delete' => ['DELETE']
        ]);
    }

    /**
     * List task files for a task
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     *
     * @OA\Get(
     *     path="/instructor/task-files",
     *     operationId="instructor::TaskFilesController::actionIndex",
     *     tags={"Instructor Task Files"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *        name="taskID",
     *        in="query",
     *        required=true,
     *        description="ID of the task",
     *        @OA\Schema(ref="#/components/schemas/int_id"),
     *     ),
     *     @OA\Parameter(
     *        name="includeAttachments",
     *        in="query",
     *        required=false,
     *        description="Include files with attachment category",
     *        @OA\Schema(type="boolean", default=true),
     *     ),
     *     @OA\Parameter(
     *        name="includeTestFiles",
     *        in="query",
     *        required=false,
     *        description="Include files with test file category",
     *        @OA\Schema(type="boolean", default=false),
     *     ),
     *     @OA\Parameter(
     *        name="includeWebTestSuites",
     *        in="query",
     *        required=false,
     *        description="Include files with web test definitions",
     *        @OA\Schema(type="boolean", default=false),
     *     ),
     *     @OA\Parameter(ref="#/components/parameters/yii2_fields"),
     *     @OA\Parameter(ref="#/components/parameters/yii2_expand"),
     *     @OA\Parameter(ref="#/components/parameters/yii2_sort"),
     *
     *     @OA\Response(
     *        response=200,
     *        description="successful operation",
     *        @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Instructor_TaskFileResource_Read")),
     *    ),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=403, ref="#/components/responses/403"),
     *    @OA\Response(response=404, ref="#/components/responses/404"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * ),
     */
    public function actionIndex(int $taskID, bool $includeAttachments = true, bool $includeTestFiles = false, bool $includeWebTestSuites = false): ActiveDataProvider
    {
        $task = TaskResource::findOne($taskID);

        if (is_null($task)) {
            throw new NotFoundHttpException(Yii::t('app', 'Task not found.'));
        }

        // Authorization check
        if (!Yii::$app->user->can('manageGroup', ['groupID' => $task->groupID])) {
            throw new ForbiddenHttpException(Yii::t('app', 'You must be an instructor of the group to perform this action!'));
        }

        $categories = [];
        if ($includeAttachments) {
            $categories[] = TaskFile::CATEGORY_ATTACHMENT;
        }
        if ($includeTestFiles) {
            $categories[] = TaskFile::CATEGORY_TESTFILE;
        }
        if ($includeWebTestSuites) {
            $categories[] = TaskFile::CATEGORY_WEB_TEST_SUITE;
        }

        $query = TaskFileResource::find()
            ->where(['taskID' => $taskID])
            ->andWhere(['in', 'category', $categories]);

        return new ActiveDataProvider(
            [
                'query' => $query,
                'pagination' => false
            ]
        );
    }

    /**
     * Download an task file
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     *
     * @OA\Get(
     *    path="/instructor/task-files/{id}/download",
     *    operationId="instructor::TaskFilesController::actionDownload",
     *    tags={"Instructor Task Files"},
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="id",
     *       in="path",
     *       required=true,
     *       description="ID of the file",
     *       @OA\Schema(ref="#/components/schemas/int_id"),
     *    ),
     *    @OA\Response(
     *        response=200,
     *        description="successful operation",
     *    ),
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

        // Authorization check
        if (!Yii::$app->user->can('manageGroup', ['groupID' => $file->task->groupID])) {
            throw new ForbiddenHttpException(Yii::t('app', 'You must be an instructor of the group to perform this action!'));
        }

        Yii::$app->response->sendFile($file->path, basename($file->path));
    }

    /**
     * Upload new task files
     * @return array|TaskFilesUploadResultResource
     * @throws BadRequestHttpException
     * @throws ForbiddenHttpException
     * @throws ServerErrorHttpException
     *
     * @OA\Post(
     *    path="/instructor/task-files",
     *    operationId="instructor::TaskFilesController::actionCreate",
     *    tags={"Instructor Task Files"},
     *    security={{"bearerAuth":{}}},
     *    @OA\RequestBody(
     *        description="files to upload and taskID",
     *        @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(ref="#/components/schemas/Instructor_UploadTaskFileResource_ScenarioDefault"),
     *        )
     *    ),
     *    @OA\Response(
     *        response=207,
     *        description="multistatus result",
     *        @OA\JsonContent(ref="#/components/schemas/Instructor_TaskFilesUploadResultResource_Read")
     *    ),
     *    @OA\Response(response=400, ref="#/components/responses/400"),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=403, ref="#/components/responses/403"),
     *    @OA\Response(response=422, ref="#/components/responses/422"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * )
     */
    public function actionCreate()
    {
        $upload = new UploadTaskFileResource();
        $upload->load(Yii::$app->request->post(), '');
        $upload->files = UploadedFile::getInstancesByName('files');

        if (!$upload->validate()) {
            $this->response->statusCode = 422;
            return $upload->errors;
        }

        $group = TaskResource::findOne($upload->taskID)->group;

        // Authorization check
        if (!Yii::$app->user->can('manageGroup', ['groupID' => $group->id])) {
            throw new ForbiddenHttpException(Yii::t('app', 'You must be an instructor of the group to perform this action!'));
        }

        $task = TaskResource::findOne($upload->taskID);
        // Check semester
        if ($task->semesterID !== SemesterResource::getActualID()) {
            throw new BadRequestHttpException(
                Yii::t('app', "You can't modify a task from a previous semester!")
            );
        }

        // Canvas synchronization check
        if ($task->category == Task::CATEGORY_TYPE_CANVAS_TASKS && $upload->category == TaskFile::CATEGORY_ATTACHMENT) {
            throw new BadRequestHttpException(
                Yii::t('app', 'This operation cannot be performed on a canvas synchronized task!')
            );
        }

        // Create folder for the task (if not exists)
        $dirPath = Yii::getAlias("@appdata/uploadedfiles/") . $upload->taskID;
        if (!file_exists($dirPath) && !is_dir($dirPath)) {
            FileHelper::createDirectory($dirPath, 0755, true);
        }

        $uploaded = [];
        $failed = [];
        foreach ($upload->files as $file) {
            try {
                $taskFile = new TaskFileResource();
                $taskFile->taskID = $upload->taskID;
                $taskFile->category = $upload->category;
                $taskFile->uploadTime = date('Y-m-d H:i:s');
                $taskFile->name = $file->baseName . '.' . $file->extension;

                if (!$taskFile->validate()) {
                    throw new AddFailedException($taskFile->name, $taskFile->errors);
                }

                /** @phpstan-ignore-next-line */
                if (!$file->saveAs($taskFile->path, !YII_ENV_TEST)) {
                    // Log
                    Yii::error(
                        "Failed to save file to the disc ($taskFile->path), error code: $file->error",
                        __METHOD__
                    );
                    throw new AddFailedException($taskFile->name, ['path' => Yii::t("app", "Failed to save file. Error logged.")]);
                }

                if ($taskFile->category == TaskFile::CATEGORY_WEB_TEST_SUITE) {
                    $this->verifyTestSanity($taskFile, $task);
                }

                if ($taskFile->save()) {
                    $uploaded[] = $taskFile;
                } else if ($taskFile->hasErrors()) {
                    throw new AddFailedException($taskFile->name, $taskFile->errors);
                } else {
                    throw new ServerErrorHttpException(Yii::t("app", "A database error occurred"));
                }
            } catch (AddFailedException $e) {
                $failedResource = new UploadFailedResource();
                $failedResource->name = $e->getIdentifier();
                $failedResource->cause = $e->getCause();
                $failed[] = $failedResource;
            }
        }

        $this->response->statusCode = 207;
        $response = new TaskFilesUploadResultResource();
        $response->uploaded = $uploaded;
        $response->failed = $failed;
        return $response;
    }

    /**
     * Delete an task file
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     * @throws ServerErrorHttpException
     * @throws BadRequestHttpException
     *
     * @OA\Delete(
     *    path="/instructor/task-files/{id}",
     *    operationId="instructor::TaskFilesController::actionDelete",
     *    tags={"Instructor Task Files"},
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="id",
     *       in="path",
     *       required=true,
     *       description="ID of the file",
     *       @OA\Schema(ref="#/components/schemas/int_id"),
     *    ),
     *    @OA\Response(
     *        response=204,
     *        description="task file deleted",
     *    ),
     *    @OA\Response(response=400, ref="#/components/responses/400"),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=403, ref="#/components/responses/403"),
     *    @OA\Response(response=404, ref="#/components/responses/404"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * )
     */
    public function actionDelete(int $id): void
    {
        $file = TaskFileResource::findOne($id);

        if (is_null($file)) {
            throw new NotFoundHttpException(Yii::t('app', 'TaskFile not found'));
        }

        // Authorization check
        if (!Yii::$app->user->can('manageGroup', ['groupID' => $file->task->groupID])) {
            throw new ForbiddenHttpException(Yii::t('app', 'You must be an instructor of the group to perform this action!'));
        }

        // Check semester
        if ($file->task->semesterID !== SemesterResource::getActualID()) {
            throw new BadRequestHttpException(
                Yii::t('app', "You can't modify a task from a previous semester!")
            );
        }

        // Canvas synchronization check
        if ($file->task->category == Task::CATEGORY_TYPE_CANVAS_TASKS && $file->category == TaskFile::CATEGORY_ATTACHMENT) {
            throw new BadRequestHttpException(
                Yii::t('app', 'This operation cannot be performed on a canvas synchronized task!')
            );
        }

        try {
            if ($file->delete()) {
                $this->response->statusCode = 204;
            } else {
                throw new Exception(Yii::t('app', 'Database Error'));
            }
        } catch (StaleObjectException $e) {
            throw new ServerErrorHttpException(Yii::t('app', 'Failed to remove TaskFile') . ' StaleObjectException:' . $e->getMessage());
        } catch (\Throwable $e) {
            throw new ServerErrorHttpException(Yii::t('app', 'Failed to remove TaskFile') . $e->getMessage());
        }
    }

    /**
     * @throws \PharException
     * @throws UnprocessableEntityHttpException
     * @throws \yii\base\Exception
     * @throws ServerErrorHttpException
     */
    private function verifyTestSanity(TaskFile $file, Task $task): void
    {
        try {
            $webTesterContainer = WebTesterContainer::createInstanceForValidation($task->testOS);
        } catch (DockerContainerException $ex) {
            throw new AddFailedException(
                $file->name,
                [Yii::t('app', 'Failed to setup container for file validation.')]
            );
        }

        $tarPath = dirname($file->path) . '/suite.tar';
        $phar = new \PharData($tarPath);
        $phar->addFile($file->path, 'suite.robot');

        try {
            $result = $webTesterContainer->validateTestScript($tarPath);
            if ($result['exitCode'] != 0) {
                unlink($file->path);
                throw new AddFailedException($file->name, [$result['stdout'] . PHP_EOL . $result['stderr']]);
            }
        } finally {
            $webTesterContainer->tearDown();
            if (file_exists($tarPath)) {
                unset($phar);
                \PharData::unlinkArchive($tarPath);
            }
        }
    }
}
