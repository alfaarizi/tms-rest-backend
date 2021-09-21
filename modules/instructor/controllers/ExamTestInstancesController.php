<?php

namespace app\modules\instructor\controllers;

use Yii;
use app\modules\instructor\resources\ExamTestResource;
use yii\data\ActiveDataProvider;
use yii\helpers\ArrayHelper;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

/**
 * This class provides access to exam test instances for instructors
 */
class ExamTestInstancesController extends BaseInstructorRestController
{
    protected function verbs()
    {
        return ArrayHelper::merge(parent::verbs(), [
            'index' => ['GET']
        ]);
    }

    /**
     * Lists instances for the given test
     * @param int $testID
     * @param null|bool $submitted
     * @return ActiveDataProvider
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     */
    public function actionIndex($testID, $submitted = null)
    {
        $test = ExamTestResource::findOne($testID);

        if (is_null($test)) {
            throw new NotFoundHttpException(Yii::t('app', 'Test does not exist'));
        }

        if (!Yii::$app->user->can('manageGroup', ['groupID' => $test->groupID])) {
            throw new ForbiddenHttpException(
                Yii::t('app', 'You must be an instructor of the group to perform this action!')
            );
        }

        $query = $test->getTestInstances();

        // If submitted is defined, add condition to the query
        if (!is_null($submitted)) {
            $query->andWhere(["submitted" => filter_var($submitted, FILTER_VALIDATE_BOOLEAN)]);
        }

        return new ActiveDataProvider(
            [
                'query' => $query,
                'pagination' => false
            ]
        );
    }
}
