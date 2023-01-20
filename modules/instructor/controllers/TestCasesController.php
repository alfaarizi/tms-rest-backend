<?php

namespace app\modules\instructor\controllers;

use app\models\Task;
use app\modules\instructor\resources\TaskResource;
use app\modules\instructor\resources\TestCaseResource;
use app\resources\SemesterResource;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use Yii;
use yii\data\ActiveDataProvider;
use yii\data\BaseDataProvider;
use yii\db\Exception;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\HttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\web\ServerErrorHttpException;
use yii\web\UploadedFile;
use yii2tech\csvgrid\CsvGrid;
use yii2tech\spreadsheet\Spreadsheet;

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
            'delete' => ['DELETE'],
            'export-test-cases' => ['GET'],
            'import-test-cases' => ['POST']
        ]);
    }

    /**
     * @throws BadRequestHttpException
     */
    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        if (!Yii::$app->params['evaluator']['enabled']) {
            throw new BadRequestHttpException(Yii::t('app', 'Evaluator is disabled. Contact the administrator for more information.'));
        }

        return true;
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

    /**
     * Exports the test cases
     * @throws BadRequestHttpException
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     *
     * @OA\Get(
     *     path="/instructor/test-cases/export-test-cases",
     *     operationId="instructor::TestCasesController::actionExportTestCases",
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
     *      @OA\Parameter(
     *        name="format",
     *        in="query",
     *        required=true,
     *        description="Format of the spreadsheet",
     *        @OA\Schema(type="string", enum={"xls", "csv"}),
     *     ),

     *     @OA\Response(
     *        response=200,
     *        description="successful operation",
     *    ),
     *    @OA\Response(response=400, ref="#/components/responses/400"),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=403, ref="#/components/responses/403"),
     *    @OA\Response(response=404, ref="#/components/responses/404"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * ),
     */
    public function actionExportTestCases(int $taskID, string $format): Response
    {
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

        $dataProvider = new ActiveDataProvider(
        [
            'query' => TestCaseResource::find()->where(['taskID' => $taskID]),
            'pagination' => [
                // Export batch size
                // Export is performed via batches
                // It improves memory usage for large datasets.
                'pageSize' => 100,
            ],
        ]);

        $columns = [
            [
                'header' => Yii::t('app', 'Arguments'),
                'attribute' => 'arguments',
            ],
            [
                'header' => Yii::t('app', 'Input'),
                'attribute' => 'input',
            ],
            [
                'header' => Yii::t('app', 'Output'),
                'attribute' => 'output',
            ],
        ];

        switch ($format) {
            case 'xls':
                return $this->exportToXls($task->name, $dataProvider, $columns);
            case 'csv':
                return $this->exportToCsv($task->name, $dataProvider, $columns);
            default:
                throw new BadRequestHttpException(Yii::t('app', 'Unsupported file format'));
        }
    }

    /**
     * Imports the test cases
     * @throws BadRequestHttpException
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     * @throws ServerErrorHttpException
     * @throws Exception
     *
     * @OA\Post(
     *     path="/instructor/test-cases/import-test-cases",
     *     operationId="instructor::TestCasesController::actionImportTestCases",
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
     *     @OA\RequestBody(
     *         description="file to upload",
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                  @OA\Property(type="string",format="binary",property="file"),
     *              )
     *         )
     *     ),
     *     @OA\Response(
     *        response=200,
     *        description="successful operation",
     *    ),
     *    @OA\Response(response=400, ref="#/components/responses/400"),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=403, ref="#/components/responses/403"),
     *    @OA\Response(response=404, ref="#/components/responses/404"),
     *    @OA\Response(response=422, ref="#/components/responses/422"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * ),
     */
    public function actionImportTestCases(int $taskID): array
    {
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

        $models = $this->importSpreadsheet($taskID);

        foreach ($models as $model) {
            if (!$model->validate()) {
                $this->response->statusCode = 422;
                return $model->errors;
            }
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            TestCaseResource::deleteAll(['taskID' => $taskID]);

            foreach ($models as $model) {
                if (!$model->save(false)) {
                    throw new ServerErrorHttpException(Yii::t("app", "A database error occurred"));
                }
            }
            $transaction->commit();
        } catch (\Throwable $e)
        {
            $transaction->rollBack();
            throw $e;
        }
        return $models;
    }

    /**
     * Creates an XLS file from the given DataProvider
     */
    private function exportToXls(string $name, BaseDataProvider $dataProvider, array $columns): Response
    {
        $exporter = new Spreadsheet(
            [
                'dataProvider' => $dataProvider,
                'columns' => $columns
            ]
        );
        return $exporter->send($name . '.xls');
    }

    /**
     * Creates a CSV file from the given DataProvider
     */
    private function exportToCsv(string $name, BaseDataProvider $dataProvider, array $columns): Response
    {
        $exporter = new CsvGrid(
            [
                'dataProvider' => $dataProvider,
                'columns' => $columns,
            ]
        );
        return $exporter->export()->send($name . '.csv');
    }

    /**
     * Imports TestCaseResource models from an uploaded Excel/CSV file
     * @throws BadRequestHttpException
     */
    private function importSpreadsheet(int $taskID): array
    {
        $models = [];

        $file = UploadedFile::getInstanceByName('file');

        switch ($file->extension) {
            case 'csv':
                $reader = new Csv();
                break;
            case 'xls':
                $reader = new Xls();
                break;
            default:
                throw new BadRequestHttpException(Yii::t('app', 'Unsupported file format'));
        }

        $reader->setReadDataOnly(true);


        $sheet = $reader->load($file->tempName)->getActiveSheet();

        $highestRow = $sheet->getHighestRow();

        for ($row = 2; $row <= $highestRow; ++$row) {
            $data = $sheet->rangeToArray('A' . $row . ':' . 'C' . $row);

            $models[$row - 2] = new TestCaseResource();
            $models[$row - 2]->scenario = TestCaseResource::SCENARIO_CREATE;

            $models[$row - 2]->taskID = $taskID;
            $models[$row - 2]->arguments = strval($data[0][0]);
            $models[$row - 2]->input = strval($data[0][1]);
            $models[$row - 2]->output = strval($data[0][2]);
        }
        return $models;
    }
}
