<?php

namespace app\controllers;

use app\resources\SemesterResource;
use yii\data\ActiveDataProvider;
use yii\filters\VerbFilter;

/**
 * This class controls the semester actions
 */
class SemestersController extends BaseRestController
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        $behaviors = parent::behaviors();

        $behaviors['verbs'] = [
            'class' => VerbFilter::class,
            'actions' => [
                'index' => ['GET']
            ],
        ];

        return $behaviors;
    }

    /**
     * Lists semesters
     * @return ActiveDataProvider
     */
    public function actionIndex()
    {
        $query = SemesterResource::find()
            ->orderBy(['id' => SORT_DESC]);

        return new ActiveDataProvider(
            [
                'query' => $query,
                'pagination' => false
            ]
        );
    }
}
