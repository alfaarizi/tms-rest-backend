<?php

namespace app\controllers;

use app\resources\SemesterResource;
use yii\data\ActiveDataProvider;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;

/**
 * This class controls the semester actions
 */
class SemestersController extends BaseRestController
{
    protected function verbs()
    {
        return ArrayHelper::merge(
            parent::verbs(),
            [
                'index' => ['GET']
            ]
        );
    }

    /**
     * List semesters
     *
     * @OA\Get(
     *     path="/common/semesters",
     *     operationId="common::SemestersController::actionIndex",
     *     tags={"Common Semesters"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(ref="#/components/parameters/yii2_fields"),
     *     @OA\Parameter(ref="#/components/parameters/yii2_expand"),
     *     @OA\Parameter(ref="#/components/parameters/yii2_sort"),
     *     @OA\Response(
     *        response=200,
     *        description="successful operation",
     *        @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Common_SemesterResource_Read")),
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/401"),
     *     @OA\Response(response=500, ref="#/components/responses/500"),
     * )
     */
    public function actionIndex(): ActiveDataProvider
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
