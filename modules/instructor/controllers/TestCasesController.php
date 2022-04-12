<?php

namespace app\modules\instructor\controllers;

use app\models\Task;
use app\modules\instructor\resources\TaskResource;
use app\modules\instructor\resources\TestCaseResource;
use app\resources\SemesterResource;
use Yii;
use yii\data\ActiveDataProvider;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\HttpException;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;
use yii\web\UnsupportedMediaTypeHttpException;

/**
 * This class provides access to test cases for instructors
 */
class TestCasesController extends BaseInstructorRestController
{
    /**
     * @inheritdoc
     */
    protected function verbs()
    {
        return array_merge(parent::verbs(), [
            'index' => ['GET'],
            'create' => ['POST'],
            'update' => ['PUT', 'PATCH'],
            'delete' => ['DELETE']
        ]);
    }

    /**
     * Get test cases for a task
     * @param $taskID
     * @return ActiveDataProvider
     * @throws BadRequestHttpException
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     *
     * @OA\Get(
     *     path="/instructor/test-cases",
     *     operationId="instructor::TestCasesController::actionIndex",
     *     tags={"Instructor Test Cases"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="taskID",
     *         in="query",
     *         required=true,
     *         description="ID of the task",
     *         explode=true,
     *         @OA\Schema(ref="#/components/schemas/int_id")
     *     ),
     *     @OA\Parameter(ref="#/components/parameters/yii2_fields"),
     *     @OA\Parameter(ref="#/components/parameters/yii2_expand"),
     *     @OA\Parameter(ref="#/components/parameters/yii2_sort"),
     *
     *     @OA\Response(
     *        response=200,
     *        description="successful operation",
     *        @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Instructor_TestCaseResource_Read")),
     *    ),
     *    @OA\Response(response=400, ref="#/components/responses/400"),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=403, ref="#/components/responses/403"),
     *    @OA\Response(response=404, ref="#/components/responses/404"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * ),
     */
    public function actionIndex($taskID)
    {
        if (!Yii::$app->params['evaluator']['enabled']) {
            throw new BadRequestHttpException(Yii::t('app', 'Auto tester is disabled. Contact the administrator for more information.'));
        }

        $task = TaskResource::findOne($taskID);

        if (is_null($task)) {
            throw new NotFoundHttpException(Yii::t('app', 'Task not found.'));
        }

        // Authorization check
        if (!Yii::$app->user->can('manageGroup', ['groupID' => $task->groupID])) {
            throw new ForbiddenHttpException(
                Yii::t('app', 'You must be an instructor of the group to perform this action!')
            );
        }

        $query = TestCaseResource::find()->where(['taskID' => $taskID]);

        return new ActiveDataProvider(
            [
                'query' => $query,
                'pagination' => false
            ]
        );
    }

    /**
     * Create a new test case for a task
     * @return TestCaseResource|array
     * @throws ForbiddenHttpException
     * @throws BadRequestHttpException
     * @throws ServerErrorHttpException
     * @throws BadRequestHttpException
     * @throws HttpException
     *
     * @OA\Post(
     *     path="/instructor/test-cases",
     *     operationId="instructor::TestCasesController::actionCreate",
     *     tags={"Instructor Test Cases"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(ref="#/components/parameters/yii2_fields"),
     *     @OA\Parameter(ref="#/components/parameters/yii2_expand"),
     *     @OA\RequestBody(
     *         description="new test case",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(ref="#/components/schemas/Instructor_TestCaseResource_ScenarioCreate"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="new test case created",
     *        @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Instructor_TestCaseResource_Read")),
     *     ),
     *    @OA\Response(response=400, ref="#/components/responses/400"),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=403, ref="#/components/responses/403"),
     *    @OA\Response(response=422, ref="#/components/responses/422"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * ),
     */
    public function actionCreate()
    {
        if (!Yii::$app->params['evaluator']['enabled']) {
            throw new BadRequestHttpException(Yii::t('app', 'Auto tester is disabled. Contact the administrator for more information.'));
        }

        $model = new TestCaseResource();
        $model->scenario = TestCaseResource::SCENARIO_CREATE;

        $model->load(Yii::$app->request->post(), '');

        if (!$model->validate()) {
            $this->response->statusCode = 422;
            return $model->errors;
        }

        $task = TaskResource::findOne($model->taskID);

        if ($task->appType == Task::APP_TYPE_WEB) {
            throw new HttpException(501, Yii::t('app', 'Automated testing for web apps is not supported!'));
        }

        // Check semester
        if ($task->semesterID !== SemesterResource::getActualID()) {
            throw new BadRequestHttpException(
                Yii::t('app', "You can't modify a task from a previous semester!")
            );
        }

        // Access check
        if (!Yii::$app->user->can('manageGroup', ['groupID' => $task->groupID])) {
            throw new ForbiddenHttpException(
                Yii::t('app', 'You must be an instructor of the group to perform this action!')
            );
        }

        if (!$model->save(false)) {
            throw new ServerErrorHttpException(Yii::t("app", "A database error occurred"));
        }

        $this->response->statusCode = 201;
        return $model;
    }

    /**
     * Update a test case
     * @param int $id
     * @return TestCaseResource|array
     * @throws ForbiddenHttpException
     * @throws ServerErrorHttpException
     * @throws NotFoundHttpException
     * @throws BadRequestHttpException
     *
     * @OA\Put(
     *     path="/instructor/test-cases/{id}",
     *     operationId="instructor::TestCasesController::actionUpdate",
     *     tags={"Instructor Test Cases"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *        name="id",
     *        in="path",
     *        required=true,
     *        description="ID of the test case",
     *        @OA\Schema(ref="#/components/schemas/int_id"),
     *     ),
     *     @OA\Parameter(ref="#/components/parameters/yii2_fields"),
     *     @OA\Parameter(ref="#/components/parameters/yii2_expand"),
     *     @OA\RequestBody(
     *         description="updated test case",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(ref="#/components/schemas/Instructor_TestCaseResource_ScenarioUpdate"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="test case updated",
     *         @OA\JsonContent(ref="#/components/schemas/Instructor_TestCaseResource_Read"),
     *     ),
     *    @OA\Response(response=400, ref="#/components/responses/400"),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=403, ref="#/components/responses/403"),
     *    @OA\Response(response=404, ref="#/components/responses/404"),
     *    @OA\Response(response=422, ref="#/components/responses/422"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * ),
     */
    public function actionUpdate($id)
    {
        if (!Yii::$app->params['evaluator']['enabled']) {
            throw new BadRequestHttpException(Yii::t('app', 'Auto tester is disabled. Contact the administrator for more information.'));
        }

        $model = TestCaseResource::findOne($id);

        if (is_null($model)) {
            throw new NotFoundHttpException(Yii::t('app', 'Test case not found.'));
        }

        // Authorization check
        if (!Yii::$app->user->can('manageGroup', ['groupID' => $model->task->groupID])) {
            throw new ForbiddenHttpException(
                Yii::t('app', 'You must be an instructor of the group to perform this action!')
            );
        }

        // Check semester
        if ($model->task->semesterID !== SemesterResource::getActualID()) {
            throw new BadRequestHttpException(
                Yii::t('app', "You can't modify a task from a previous semester!")
            );
        }

        $model->scenario = TestCaseResource::SCENARIO_UPDATE;
        $model->load(Yii::$app->request->post(), '');

        if (!$model->validate()) {
            $this->response->statusCode = 422;
            return $model->errors;
        }

        if (!$model->save(false)) {
            throw new ServerErrorHttpException(Yii::t("app", "A database error occurred"));
        }
        return $model;
    }

    /**
     * Delete a test case
     * @param int $id
     * @throws ForbiddenHttpException
     * @throws ServerErrorHttpException
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     * @throws NotFoundHttpException
     * @throws BadRequestHttpException
     *
     *
     * @OA\Delete(
     *     path="/instructor/test-cases/{id}",
     *     operationId="instructor::TestCasesController::actionDelete",
     *     tags={"Instructor Test Cases"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *        name="id",
     *        in="path",
     *        required=true,
     *        description="ID of the test case",
     *        @OA\Schema(ref="#/components/schemas/int_id"),
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="test case deleted",
     *     ),
     *    @OA\Response(response=400, ref="#/components/responses/400"),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=403, ref="#/components/responses/403"),
     *    @OA\Response(response=404, ref="#/components/responses/404"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * )
     */
    public function actionDelete($id)
    {
        if (!Yii::$app->params['evaluator']['enabled']) {
            throw new BadRequestHttpException(Yii::t('app', 'Auto tester is disabled. Contact the administrator for more information.'));
        }

        $model = TestCaseResource::findOne($id);

        if (is_null($model)) {
            throw new NotFoundHttpException(Yii::t('app', 'Test case not found.'));
        }

        // Authorization check
        if (!Yii::$app->user->can('manageGroup', ['groupID' => $model->task->groupID])) {
            throw new ForbiddenHttpException(
                Yii::t('app', 'You must be an instructor of the group to perform this action!')
            );
        }

        // Check semester
        if ($model->task->semesterID !== SemesterResource::getActualID()) {
            throw new BadRequestHttpException(
                Yii::t('app', "You can't modify a task from a previous semester!")
            );
        }

        try {
            $model->delete();
            $this->response->statusCode = 204;
        } catch (yii\base\ErrorException $e) {
            throw new ServerErrorHttpException(Yii::t('app', 'A database error occurred'));
        }
    }
}
