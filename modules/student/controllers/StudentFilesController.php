<?php

namespace app\modules\student\controllers;

use app\components\GitManager;
use app\models\User;
use app\modules\student\resources\StudentFileUploadResource;
use Yii;
use app\modules\student\resources\TaskResource;
use app\modules\student\helpers\PermissionHelpers;
use app\modules\student\resources\StudentFileResource;
use yii\helpers\FileHelper;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;
use yii\web\UploadedFile;

/**
 * This class provides access to student files for students
 */
class StudentFilesController extends BaseStudentRestController
{
    /**
     * @inheritdoc
     */
    protected function verbs()
    {
        return array_merge(parent::verbs(), [
            'view' => ['GET'],
            'download' => ['GET'],
            'upload' => ['POST']
        ]);
    }

    /**
     * View StudentFile information
     * @param int $id
     * @return StudentFileResource|null
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     */
    public function actionView($id)
    {
        $file = StudentFileResource::findOne($id);

        if (is_null($file)) {
            throw new NotFoundHttpException(Yii::t('app', 'Student File not found.'));
        }

        PermissionHelpers::isItMyTask($file->taskID);
        PermissionHelpers::isItMyStudentFile($file);

        return $file;
    }

    /**
     * Download uploaded solution
     * @param int $id
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     */
    public function actionDownload($id)
    {
        $file = StudentFileResource::findOne($id);

        if (is_null($file)) {
            throw new NotFoundHttpException(Yii::t('app', 'Student File not found.'));
        }

        PermissionHelpers::isItMyTask($file->taskID);
        PermissionHelpers::isItMyStudentFile($file);

        Yii::$app->response->sendFile($file->path, basename($file->path));
    }

    /**
     * @return StudentFileResource|array
     * @throws BadRequestHttpException
     * @throws ForbiddenHttpException
     * @throws ServerErrorHttpException
     */
    public function actionUpload()
    {
        $model = new StudentFileUploadResource();
        $model->load(Yii::$app->request->post(), '');
        $model->file = UploadedFile::getInstanceByName('file');

        if (!$model->validate()) {
            $this->response->statusCode = 422;
            return $model->errors;
        }

        // Find task
        $task = TaskResource::findOne($model->taskID);

        // Permission checks
        PermissionHelpers::isItMyTask($task->id);
        PermissionHelpers::checkIfTaskAvailable($task);

        // Canvas synchronization check
        if ($task->group->isCanvasCourse) {
            throw new BadRequestHttpException(
                Yii::t('app', 'This operation cannot be performed on a canvas synchronized course!')
            );
        }

        // Get previous file
        $prevStudentFile = StudentFileResource::findOne(['uploaderID' => Yii::$app->user->id, 'taskID' => $task->id]);

        // Verify that the task is open for submissions or the student has a special late submission permission.
        if (strtotime($task->hardDeadline) < time() && (is_null(
                    $prevStudentFile
                ) || $prevStudentFile->isAccepted !== "Late Submission")) {
            throw new BadRequestHttpException(Yii::t('app', 'The hard deadline of the solution has passed!'));
        }

        // Verify that the student has no accepted solution yet.
        if (!is_null($prevStudentFile) && $prevStudentFile->isAccepted === "Accepted") {
            throw new BadRequestHttpException(Yii::t('app', 'Your solution was accepted!'));
        }

        return $this->saveFile(
            $prevStudentFile,
            $model->file,
            $task->id,
            Yii::$app->params['versionControl']['enabled'] && $task->isVersionControlled
        );
    }

    /**
     * Save the files to the disk (and to the git repository)
     * @param StudentFileResource|mixed $prevStudentFile
     * @param UploadedFile $newFile
     * @param int $taskID
     * @return StudentFileResource
     * @throws ServerErrorHttpException
     * @throws \Cz\Git\GitException
     */
    private function saveFile($prevStudentFile, $newFile, $taskID, $versionControlled)
    {
        // Set basepath
        $basepath = Yii::$app->basePath . '/' . Yii::$app->params['data_dir']
            . '/uploadedfiles/' . $taskID . '/' . strtolower(Yii::$app->user->identity->neptun) . '/';

        if (!file_exists($basepath)) {
            mkdir($basepath, 0755, true);
        }

        $zipFiles = FileHelper::findFiles($basepath, ['only' => ['*.zip'], 'recursive' => false]);
        if ($zipFiles != null) {
            unlink($zipFiles[0]);
        }

        // Save file to disc.
        $result = $newFile->saveAs($basepath . $newFile->name, !YII_ENV_TEST);
        if (!$result) {
            // Log
            Yii::error(
                "Failed to save file to the disc ($newFile->name), error code: $newFile->error",
                __METHOD__
            );
            throw new ServerErrorHttpException(Yii::t('app', "Failed to save file. Error logged."));
        }

        if ($versionControlled) {
            // Find unique string
            $dirs = FileHelper::findDirectories($basepath, ['recursive' => false]);
            rsort($dirs);
            $repopath = $basepath . basename($dirs[0]) . '/';
            $zipPath = $basepath . $newFile->name;
            GitManager::uploadToRepo($repopath, $zipPath);
        }

        $uploader = User::findOne(Yii::$app->user->id);
        $studentFile = null;
        if ($prevStudentFile == null) {
            $studentFile = new StudentFileResource();
            //Set details
            $studentFile->taskID = $taskID;
            $studentFile->isAccepted = "Uploaded";
            $studentFile->grade = null;
            $studentFile->notes = "";
            $studentFile->uploaderID = $uploader->id;
            $studentFile->name = basename($newFile->name);
            $studentFile->uploadTime = date('Y-m-d H:i:s');
            $studentFile->isVersionControlled = $versionControlled ? 1 : 0;
        } else {
            $studentFile = $prevStudentFile;
            $studentFile->name = basename($newFile->name);
            $studentFile->uploadTime = date('Y-m-d H:i:s');
            $studentFile->isAccepted = "Updated";
        }

        if ($studentFile->save()) {
            Yii::info(
                "A new solution has been uploaded for " .
                "{$studentFile->task->name} ($taskID)",
                __METHOD__
            );
            return $studentFile;
        } else {
            throw new ServerErrorHttpException(Yii::t('app', "A database error occurred"));
        }
    }
}
