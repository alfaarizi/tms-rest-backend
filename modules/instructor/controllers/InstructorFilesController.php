<?php

namespace app\modules\instructor\controllers;

use app\exceptions\AddFailedException;
use app\models\InstructorFile;
use app\modules\instructor\resources\InstructorFileResource;
use app\modules\instructor\resources\TaskResource;
use app\modules\instructor\resources\UploadInstructorFileResource;
use app\resources\SemesterResource;
use Yii;
use yii\data\ActiveDataProvider;
use yii\db\Exception;
use yii\db\StaleObjectException;
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
     * @param int $taskID
     * @param bool $includeAttachments
     * @param bool $includeTestFiles
     * @return ActiveDataProvider
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
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
     * @param int $id
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
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
     * @return array|array[]
     * @throws BadRequestHttpException
     * @throws ForbiddenHttpException
     * @throws ServerErrorHttpException
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
        $dirPath = Yii::$app->basePath . '/' . Yii::$app->params['data_dir'] . '/uploadedfiles/' . $upload->taskID;
        if (!file_exists($dirPath) && !is_dir($dirPath)) {
            mkdir($dirPath, 0755, true);
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
                $failed[] = [
                    'name' => $e->getIdentifier(),
                    'cause' => $e->getCause()
                ];
            }
        }

        $this->response->statusCode = 207;
        return [
            'uploaded' => $uploaded,
            'failed' => $failed
        ];
    }

    /**
     * @param int $id
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     * @throws ServerErrorHttpException
     * @throws BadRequestHttpException
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
        if ($file->task->group->isCanvasCourse) {
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
