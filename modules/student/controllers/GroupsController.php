<?php

namespace app\modules\student\controllers;

use app\modules\student\helpers\PermissionHelpers;
use app\modules\student\resources\GroupResource;
use Yii;
use yii\data\ActiveDataProvider;
use yii\web\NotFoundHttpException;

/**
 * This class controls the group actions
 */
class GroupsController extends BaseStudentRestController
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
     * Lists groups for the current student in a given semester
     * @param integer $semesterID
     * @return ActiveDataProvider
     */
    public function actionIndex($semesterID)
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
     * @param int $id
     * @return GroupResource|null
     * @throws NotFoundHttpException
     * @throws \yii\web\ForbiddenHttpException
     */
    public function actionView($id)
    {
        $group = GroupResource::findOne($id);
        if (is_null($group)) {
            throw new NotFoundHttpException(Yii::t("app", "Group not found."));
        }

        PermissionHelpers::isMyGroup($id);

        return $group;
    }
}
