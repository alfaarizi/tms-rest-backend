<?php

namespace app\modules\instructor\controllers;

use app\exceptions\AddFailedException;
use app\models\InstructorFile;
use app\modules\instructor\resources\InstructorFileResource;
use app\modules\instructor\resources\InstructorFilesUploadResultResource;
use app\modules\instructor\resources\TaskResource;
use app\modules\instructor\resources\UploadFailedResource;
use app\modules\instructor\resources\UploadInstructorFileResource;
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
use yii\web\UploadedFile;

/**
 * This class provides access to instructor files for instructors
 */
class InstructorFilesController extends BaseInstructorRestController
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
     * List instructor files for a task
     * @param int $taskID
     * @param bool $includeAttachments
     * @param bool $includeTestFiles
     * @return ActiveDataProvider
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     *
     * @OA\Get(
     *     path="/instructor/instructor-files",
     *     operationId="instructor::InstructorFilesController::actionIndex",
     *     tags={"Instructor Instructor Files"},
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
     *     @OA\Parameter(ref="#/components/parameters/yii2_fields"),
     *     @OA\Parameter(ref="#/components/parameters/yii2_expand"),
     *     @OA\Parameter(ref="#/components/parameters/yii2_sort"),
     *
     *     @OA\Response(
     *        response=200,
     *        description="successful operation",
     *        @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Instructor_InstructorFileResource_Read")),
     *    ),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=403, ref="#/components/responses/403"),
     *    @OA\Response(response=404, ref="#/components/responses/404"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * ),
     */
    public function actionIndex($taskID, $includeAttachments = true, $includeTestFiles = false)
    {
        $task = TaskResource::findOne($taskID);

        if (is_null($task)) {
            throw new NotFoundHttpException(Yii::t('app', 'Task not found.'));
        }

        // Authorization check
        if (!Yii::$app->user->can('manageGroup', ['groupID' => $task->groupID])) {
            throw new ForbiddenHttpException(Yii::t('app', 'You must be an instructor of the group to perform this action!'));
        }

        // boolean values in QS passes as strings
        $includeAttachments = filter_var($includeAttachments, FILTER_VALIDATE_BOOLEAN);
        $includeTestFiles = filter_var($includeTestFiles, FILTER_VALIDATE_BOOLEAN);

        $categories = [];
        if ($includeAttachments) {
            $categories[] = InstructorFile::CATEGORY_ATTACHMENT;
        }
        if ($includeTestFiles) {
            $categories[] = InstructorFile::CATEGORY_TESTFILE;
        }

        $query = InstructorFileResource::find()
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
     * Download an instructor file
     * @param int $id
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     *
     * @OA\Get(
     *    path="/instructor/instructor-files/{id}/download",
     *    operationId="instructor::InstructorFilesController::actionDownload",
     *    tags={"Instructor Instructor Files"},
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
    public function actionDownload($id)
    {
        $file = InstructorFileResource::findOne($id);

        if (is_null($file)) {
            throw new NotFoundHttpException(Yii::t('app', 'Instructor File not found.'));
        }

        // Authorization check
        if (!Yii::$app->user->can('manageGroup', ['groupID' => $file->task->groupID])) {
            throw new ForbiddenHttpException(Yii::t('app', 'You must be an instructor of the group to perform this action!'));
        }

        Yii::$app->response->sendFile($file->path, basename($file->path));
    }

    /**
     * Upload new instructor files
     * @return array|InstructorFilesUploadResultResource
     * @throws BadRequestHttpException
     * @throws ForbiddenHttpException
     * @throws ServerErrorHttpException
     *
     * @OA\Post(
     *    path="/instructor/instructor-files",
     *    operationId="instructor::InstructorFilesController::actionCreate",
     *    tags={"Instructor Instructor Files"},
     *    security={{"bearerAuth":{}}},
     *    @OA\RequestBody(
     *        description="files to upload and taskID",
     *        @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(ref="#/components/schemas/Instructor_UploadInstructorFileResource_ScenarioDefault"),
     *        )
     *    ),
     *    @OA\Response(
     *        response=207,
     *        description="multistatus result",
     *        @OA\JsonContent(ref="#/components/schemas/Instructor_InstructorFilesUploadResultResource_Read")
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
        $upload = new UploadInstructorFileResource();
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

        // Check semester
        if (TaskResource::findOne($upload->taskID)->semesterID !== SemesterResource::getActualID()) {
            throw new BadRequestHttpException(
                Yii::t('app', "You can't modify a task from a previous semester!")
            );
        }

        // Canvas synchronization check
        if ($group->isCanvasCourse && $upload->category == InstructorFile::CATEGORY_ATTACHMENT) {
            throw new BadRequestHttpException(
                Yii::t('app', 'This operation cannot be performed on a canvas synchronized course!')
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
                $instructorFile = new InstructorFileResource();
                $instructorFile->taskID = $upload->taskID;
                $instructorFile->category = $upload->category;
                $instructorFile->uploadTime = date('Y-m-d H:i:s');
                $instructorFile->name = $file->baseName . '.' . $file->extension;

                if (!$instructorFile->validate()) {
                    throw new AddFailedException($instructorFile->name, $instructorFile->errors);
                }

                if (!$file->saveAs($instructorFile->path, !YII_ENV_TEST)) {
                    // Log
                    Yii::error(
                        "Failed to save file to the disc ($instructorFile->path), error code: $file->error",
                        __METHOD__
                    );
                    throw new AddFailedException($instructorFile->name, ['path' => Yii::t("app", "Failed to save file. Error logged." )]);
                }

                if ($instructorFile->save()) {
                    $uploaded[] = $instructorFile;
                } else if ($instructorFile->hasErrors()) {
                    throw new AddFailedException($instructorFile->name, $instructorFile->errors);
                } else {
                    throw new ServerErrorHttpException(Yii::t("app", "A database error occurred" ));
                }
            } catch (AddFailedException $e) {
                $failedResource = new UploadFailedResource();
                $failedResource->name = $e->getIdentifier();
                $failedResource->cause = $e->getCause();
                $failed[] = $failedResource;
            }
        }

        $this->response->statusCode = 207;
        $response = new InstructorFilesUploadResultResource();
        $response->uploaded = $uploaded;
        $response->failed = $failed;
        return $response;
    }

    /**
     * Delete an instructor file
     * @param int $id
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     * @throws ServerErrorHttpException
     * @throws BadRequestHttpException
     *
     * @OA\Delete(
     *    path="/instructor/instructor-files/{id}",
     *    operationId="instructor::InstructorFilesController::actionDelete",
     *    tags={"Instructor Instructor Files"},
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
     *        description="instructor file deleted",
     *    ),
     *    @OA\Response(response=400, ref="#/components/responses/400"),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=403, ref="#/components/responses/403"),
     *    @OA\Response(response=404, ref="#/components/responses/404"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * )
     */
    public function actionDelete($id)
    {
        $file = InstructorFileResource::findOne($id);

        if (is_null($file)) {
            throw new NotFoundHttpException(Yii::t('app', 'InstructorFile not found'));
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
        if ($file->task->group->isCanvasCourse && $file->category == InstructorFile::CATEGORY_ATTACHMENT) {
            throw new BadRequestHttpException(
                Yii::t('app', 'This operation cannot be performed on a canvas synchronized course!')
            );
        }

        try {
            if ($file->delete()) {
                $this->response->statusCode = 204;
            } else {
                throw new Exception(Yii::t('app', 'Database Error'));
            }
        } catch (StaleObjectException $e) {
            throw new ServerErrorHttpException(Yii::t('app', 'Failed to remove InstructorFile') . ' StaleObjectException:' . $e->getMessage());
        } catch (\Throwable $e) {
            throw new ServerErrorHttpException(Yii::t('app', 'Failed to remove InstructorFile') . $e->getMessage());
        }
    }
}
