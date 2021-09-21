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
 * This class provides access to tasks for students
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
     * Lists tasks for the current user in a given group
     * @param int $groupID
     * @return array
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
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
     * View task details
     * @param int $id
     * @return TaskResource|array|null
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
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
