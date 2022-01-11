<?php

namespace app\modules\student\controllers;

use app\models\InstructorFile;
use app\modules\student\resources\TaskResource;
use Yii;
use app\modules\student\resources\InstructorFileResource;
use app\modules\student\helpers\PermissionHelpers;
use yii\data\ActiveDataProvider;
use yii\web\NotFoundHttpException;
use yii\web\ForbiddenHttpException;

/**
 * This class provides access to instructor files for students
 */
class InstructorFilesController extends BaseStudentRestController
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
     * Lists public instructor files for a task
     * @param int $taskID
     * @return ActiveDataProvider
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     */
    public function actionIndex($taskID)
    {
        $task = TaskResource::findOne($taskID);

        if (is_null($task)) {
            throw new NotFoundHttpException(Yii::t('app', 'Task not found.'));
        }

        PermissionHelpers::isItMyTask($taskID);
        PermissionHelpers::checkIfTaskAvailable($task);

        return new ActiveDataProvider([
            'query' => $task->getInstructorFiles()->where(['category' => InstructorFile::CATEGORY_ATTACHMENT]),
            'pagination' => false
        ]);
    }

    /**
     * Sends the requested instructor file to the user's browser.
     * @param int $id is the id of the file
     * @throws NotFoundHttpException
     * @throws ForbiddenHttpException;
     */
    public function actionDownload($id)
    {
        $file = InstructorFileResource::findOne($id);

        if (is_null($file)) {
            throw new NotFoundHttpException(Yii::t('app', 'Instructor File not found.'));
        }

        PermissionHelpers::isItMyTask($file->taskID);
        PermissionHelpers::checkIfTaskAvailable($file->task);

        if (!$file->isAttachment) {
            throw new ForbiddenHttpException(Yii::t('app', 'Instructor File not available.'));
        }

        Yii::$app->response->sendFile($file->path, basename($file->path));
    }
}
