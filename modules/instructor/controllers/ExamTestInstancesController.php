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
     * List test instances for the given test
     * @param int $testID
     * @param null|bool $submitted
     * @return ActiveDataProvider
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     *
     * @OA\Get(
     *     path="/instructor/exam-test-instances",
     *     operationId="instructor::ExamTestInstancesController::actionIndex",
     *     tags={"Instructor Exam Test Instances"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="testID",
     *         in="query",
     *         required=true,
     *         description="ID of the test",
     *         explode=true,
     *         @OA\Schema(ref="#/components/schemas/int_id")
     *     ),
     *     @OA\Parameter(
     *         name="submitted",
     *         in="query",
     *         required=false,
     *         description="null or empty: all instances, true: submitted instances, false: not submitted instances",
     *         explode=true,
     *         @OA\Schema(type="boolean")
     *     ),
     *     @OA\Parameter(ref="#/components/parameters/yii2_fields"),
     *     @OA\Parameter(ref="#/components/parameters/yii2_expand"),
     *     @OA\Parameter(ref="#/components/parameters/yii2_sort"),
     *
     *     @OA\Response(
     *        response=200,
     *        description="successful operation",
     *        @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Instructor_ExamTestInstanceResource_Read")),
     *    ),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=403, ref="#/components/responses/403"),
     *    @OA\Response(response=404, ref="#/components/responses/404"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * ),
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
