<?php

namespace app\modules\instructor\controllers;

use app\components\CanvasIntegration;
use app\components\CodeCompass;
use app\components\CodeCompassHelper;
use app\components\GitManager;
use app\components\JwtHelper;
use app\models\CodeCompassInstance;
use app\models\Submission;
use app\models\User;
use app\modules\instructor\resources\CodeCompassInstanceResource;
use app\modules\instructor\resources\GroupResource;
use app\modules\instructor\resources\IpAddressResource;
use app\modules\instructor\resources\JwtValidationResource;
use app\modules\instructor\resources\SubmissionResource;
use app\modules\instructor\resources\TaskResource;
use app\resources\AutoTesterResultResource;
use app\resources\JwtResource;
use app\resources\SemesterResource;
use app\resources\UserResource;
use Yii;
use yii\data\ActiveDataProvider;
use yii\data\BaseDataProvider;
use yii\db\StaleObjectException;
use yii\helpers\FileHelper;
use yii\web\BadRequestHttpException;
use yii\web\ConflictHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;
use yii\web\UnauthorizedHttpException;
use yii2tech\csvgrid\CsvGrid;
use yii2tech\spreadsheet\Spreadsheet;

/**
 * This class provides access to student files for instructors
 */
class SubmissionsController extends BaseInstructorRestController
{
    /**
     * @inheritdoc
     */
    protected function verbs()
    {
        return array_merge(parent::verbs(), [
            'list-for-task' => ['GET'],
            'list-for-student' => ['GET'],
            'view' => ['GET'],
            'update' => ['PATCH'],
            'set-personal-deadline' => ['PATCH'],
            'download' => ['GET'],
            'download-all-files' => ['GET'],
            'start-code-compass' => ['POST'],
            'stop-code-compass' => ['POST'],
            'auto-tester-results' => ['GET'],
            'download-report' => ['GET'],
            'jwt-generate' => ['POST'],
            'jwt-validate' => ['GET'],
        ]);
    }

    /**
     * List student files for a task
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     *
     * @OA\Get(
     *     path="/instructor/submissions/list-for-task",
     *     operationId="instructor::SubmissionsController::actionListForTask",
     *     tags={"Instructor Student Files"},
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
     *        @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Instructor_SubmissionResource_Read")),
     *    ),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=403, ref="#/components/responses/403"),
     *    @OA\Response(response=404, ref="#/components/responses/404"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * ),
     */
    public function actionListForTask(int $taskID): ActiveDataProvider
    {
        $task = TaskResource::findOne($taskID);

        if (is_null($task)) {
            throw new NotFoundHttpException(Yii::t('app', 'Task not found'));
        }

        // Authorization check
        if (!Yii::$app->user->can('manageGroup', ['groupID' => $task->groupID])) {
            throw new ForbiddenHttpException(Yii::t('app', 'You must be an instructor of the group to perform this action!'));
        }

        $query = SubmissionResource::find()->where(['taskID' => $taskID]);

        return new ActiveDataProvider(
            [
              'query' => $query,
              'pagination' => false
            ]
        );
    }

    /**
     * List student files for a task, then export the list to a spreadsheet
     * @throws BadRequestHttpException
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     *
     *  @OA\Get(
     *     path="/instructor/submissions/export-spreadsheet",
     *     operationId="instructor::SubmissionsController::actionExportSpreadsheet",
     *     tags={"Instructor Student Files"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *        name="taskID",
     *        in="query",
     *        required=true,
     *        description="ID of the task",
     *        @OA\Schema(ref="#/components/schemas/int_id"),
     *     ),
     *     @OA\Parameter(
     *        name="format",
     *        in="query",
     *        required=true,
     *        description="Format of the spreadsheet",
     *        @OA\Schema(type="string", enum={"xlsx", "csv"}),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="successful operation",
     *     ),
     *    @OA\Response(response=400, ref="#/components/responses/400"),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=403, ref="#/components/responses/403"),
     *    @OA\Response(response=404, ref="#/components/responses/404"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * ),
     */
    public function actionExportSpreadsheet(int $taskID, string $format): \yii\web\Response
    {
        $task = TaskResource::findOne($taskID);

        if (is_null($task)) {
            throw new NotFoundHttpException(Yii::t('app', 'Task not found'));
        }

        // Authorization check
        if (!Yii::$app->user->can('manageGroup', ['groupID' => $task->groupID])) {
            throw new ForbiddenHttpException(Yii::t('app', 'You must be an instructor of the group to perform this action!'));
        }

        // Create dataProvide for student files
        $dataProvider = new ActiveDataProvider(
            [
                'query' => Submission::find()->where(['taskID' => $taskID]),
                'pagination' => [
                    // Export batch size
                    // Export is performed via batches
                    // It improves memory usage for large datasets.
                    'pageSize' => 100,
                ],
            ]
        );

        // Columns with headers
        $columns = [
            [
                'header' => Yii::t('app', 'Name'),
                'attribute' => 'uploader.name',
            ],
            [
                'header' => 'User code',
                'attribute' => 'uploader.userCode',
            ],
            [
                'header' => Yii::t('app', 'Upload Time'),
                'attribute' => 'uploadTime',
            ],
            [
                'header' => Yii::t('app', 'Status'),
                'attribute' => 'translatedStatus',
            ],
            [
                'header' => Yii::t('app', 'Grade'),
                'attribute' => 'uploadTime',
            ],
            [
                'header' => Yii::t('app', 'Grade'),
                'attribute' => 'grade',
            ],
            [
                'header' => Yii::t('app', 'Notes'),
                'attribute' => 'notes',
            ],
            [
                'header' => Yii::t('app', 'Graded By'),
                'attribute' => 'grader.name',
            ],
            [
                'header' => Yii::t('app', 'IP addresses'),
                'value' => function ($model) {
                    /** @var $model Submission */
                    return implode(', ', $model->ipAddresses);
                }
            ],
        ];

        if ($format == 'xlsx') {
            return $this->exportToXlsx($task->name, $dataProvider, $columns);
        } elseif ($format == 'csv') {
            return $this->exportToCsv($task->name, $dataProvider, $columns);
        } else {
            throw new BadRequestHttpException(Yii::t('app', 'Unsupported file format'));
        }
    }

    /**
     * Creates a xlsx file from the given DataProvider
     */
    private function exportToXlsx(string $name, BaseDataProvider $dataProvider, array $columns): \yii\web\Response
    {
        $exporter = new Spreadsheet(
            [
                'dataProvider' => $dataProvider,
                'columns' => $columns
            ]
        );
        return $exporter->send($name . '.xlsx');
    }

    /**
     * Creates a csv file from the given DataProvider
     */
    private function exportToCsv(string $name, BaseDataProvider $dataProvider, array $columns): \yii\web\Response
    {
        $exporter = new CsvGrid(
            [
                'dataProvider' => $dataProvider,
                'columns' => $columns
            ]
        );
        return $exporter->export()->send($name . '.csv');
    }

    /**
     * List student files for a group and a student
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     *
     * @OA\Get(
     *     path="/instructor/submissions/list-for-student",
     *     operationId="instructor::SubmissionsController::actionListForStudent",
     *     tags={"Instructor Student Files"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="groupID",
     *         in="query",
     *         required=true,
     *         description="ID of the group",
     *         explode=true,
     *         @OA\Schema(ref="#/components/schemas/int_id")
     *     ),
     *     @OA\Parameter(
     *         name="uploaderID",
     *         in="query",
     *         required=true,
     *         description="ID of the student",
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
     *        @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Instructor_SubmissionResource_Read")),
     *    ),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=403, ref="#/components/responses/403"),
     *    @OA\Response(response=404, ref="#/components/responses/404"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * ),
     */
    public function actionListForStudent(int $groupID, int $uploaderID): ActiveDataProvider
    {
        $group = GroupResource::findOne($groupID);
        $student = UserResource::findOne($uploaderID);

        if (is_null($group)) {
            throw new NotFoundHttpException(Yii::t('app', 'Group not found'));
        }

        if (is_null($student)) {
            throw new NotFoundHttpException(Yii::t('app', 'Student not found'));
        }

        // Authorization check
        if (!Yii::$app->user->can('manageGroup', ['groupID' => $groupID])) {
            throw new ForbiddenHttpException(Yii::t('app', 'You must be an instructor of the group to perform this action!'));
        }

        $query = SubmissionResource::find()
            ->innerJoinWith('task t')
            ->where(['t.groupID' => $groupID])
            ->andWhere(['uploaderID' => $uploaderID]);

        return new ActiveDataProvider(
            [
              'query' => $query,
              'pagination' => false
          ]);
    }

    /**
     * Get information about an uploaded file
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     *
     * @OA\Get(
     *     path="/instructor/submissions/{id}",
     *     operationId="instructor::SubmissionsController::actionView",
     *     tags={"Instructor Student Files"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(ref="#/components/parameters/yii2_fields"),
     *     @OA\Parameter(ref="#/components/parameters/yii2_expand"),
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the student file",
     *         @OA\Schema(ref="#/components/schemas/int_id")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="successful operation",
     *         @OA\JsonContent(ref="#/components/schemas/Instructor_SubmissionResource_Read"),
     *     ),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=403, ref="#/components/responses/403"),
     *    @OA\Response(response=404, ref="#/components/responses/404"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * ),
     */
    public function actionView(int $id): SubmissionResource
    {
        $submission = SubmissionResource::findOne($id);

        if (is_null($submission)) {
            throw new NotFoundHttpException(Yii::t('app', 'Submission not found'));
        }

        // Authorization check
        if (!Yii::$app->user->can('manageGroup', ['groupID' => $submission->task->groupID])) {
            throw new ForbiddenHttpException(Yii::t('app', 'You must be an instructor of the group to perform this action!'));
        }

        return $submission;
    }

    /**
     * Grade solution (update student file)
     * @return SubmissionResource|array|null
     * @throws BadRequestHttpException
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     * @throws ServerErrorHttpException
     *
     * @OA\Patch(
     *     path="/instructor/submissions/{id}",
     *     operationId="instructor::SubmissionsController::actionUpdate",
     *     tags={"Instructor Student Files"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *        name="id",
     *        in="path",
     *        required=true,
     *        description="ID of the student file",
     *        @OA\Schema(ref="#/components/schemas/int_id"),
     *     ),
     *     @OA\Parameter(ref="#/components/parameters/yii2_fields"),
     *     @OA\Parameter(ref="#/components/parameters/yii2_expand"),
     *     @OA\RequestBody(
     *         description="updated student file",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(ref="#/components/schemas/Instructor_SubmissionResource_ScenarioGrade"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="student file updated",
     *         @OA\JsonContent(ref="#/components/schemas/Instructor_TestCaseResource_Read"),
     *     ),
     *    @OA\Response(response=400, ref="#/components/responses/400"),
     *    @OA\Response(
     *        response=401,
     *        description="Unauthorized: missing, invalid or expired access or Canvas token. If Canvas login is required, the Proxy-Authenticate header containains the login URL.",
     *        @OA\JsonContent(ref="#/components/schemas/Yii2Error"),
     *    ),
     *    @OA\Response(response=403, ref="#/components/responses/403"),
     *    @OA\Response(response=404, ref="#/components/responses/404"),
     *    @OA\Response(response=422, ref="#/components/responses/422"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * ),
     */
    public function actionUpdate(int $id)
    {
        $submission = SubmissionResource::findOne($id);

        if (is_null($submission)) {
            throw new NotFoundHttpException(Yii::t('app', 'Submission not found'));
        }

        // Authorization check
        if (!Yii::$app->user->can('manageGroup', ['groupID' => $submission->task->groupID])) {
            throw new ForbiddenHttpException(Yii::t('app', 'You must be an instructor of the group to perform this action!'));
        }

        // Check semester
        if (SemesterResource::getActualID() !== $submission->task->semesterID) {
            throw new BadRequestHttpException(
                Yii::t('app', "You can't modify grade a solution from a previous semester!")
            );
        }

        $submission->scenario = SubmissionResource::SCENARIO_GRADE;
        $submission->load(Yii::$app->request->post(), '');
        $submission->graderID = Yii::$app->user->id;
        if (!$submission->validate()) {
            $this->response->statusCode = 422;
            return $submission->errors;
        }

        if ($submission->autoTesterStatus == Submission::AUTO_TESTER_STATUS_IN_PROGRESS) {
            $submission->autoTesterStatus = Submission::AUTO_TESTER_STATUS_NOT_TESTED;
        }

        // Disable Git push if submission was accepted
        if (Yii::$app->params['versionControl']['enabled'] && $submission->task->isVersionControlled) {
            GitManager::updateSubmissionHooks($submission);
        }

        $isCanvasSynced = Yii::$app->params['canvas']['enabled'] && !empty($submission->canvasID);
        // Upload to the canvas if synchronized
        if ($isCanvasSynced) {
            $user = User::findIdentity(Yii::$app->user->id);
            if (!$user->isAuthenticatedInCanvas) {
                $this->response->statusCode = 401;
                $this->response->headers->add('Proxy-Authenticate', CanvasIntegration::getLoginURL());
                return null;
            }
        }

        if (!$submission->save()) {
            throw new ServerErrorHttpException(Yii::t('app',  'Failed to save Submission. Message: ') . Yii::t('app', 'A database error occurred'));
        }

        // Log
        Yii::info(
            "Solution #$submission->id graded " .
            "for task {$submission->task->name} (#$submission->taskID) " .
            "with status $submission->status, grade $submission->grade and notes: $submission->notes",
            __METHOD__
        );


        // E-mail notification
        if ($submission->uploader->notificationEmail) {
            $originalLanguage = Yii::$app->language;
            Yii::$app->language = $submission->uploader->locale;
            Yii::$app->mailer->compose('student/markSolution', [
                'submission' => $submission,
                'actor' => Yii::$app->user->identity,
            ])
                ->setFrom(Yii::$app->params['systemEmail'])
                ->setTo($submission->uploader->notificationEmail)
                ->setSubject(Yii::t('app/mail', 'Graded submission'))
                ->send();
            Yii::$app->language = $originalLanguage;
        }

        // Upload to the canvas if synchronized
        if ($isCanvasSynced) {
            $canvas = new CanvasIntegration();
            if ($canvas->refreshCanvasToken($user)) {
                $canvas->uploadGradeToCanvas($submission->id);
            } else {
                throw new ServerErrorHttpException(Yii::t('app', 'Failed to refresh Canvas Token.'));
            }
        }

        return $submission;
    }

    /**
     * Set personal deadline for user
     * @return SubmissionResource|array|null
     * @throws BadRequestHttpException
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     * @throws ServerErrorHttpException
     *
     * @OA\Patch(
     *     path="/instructor/submissions/{id}/set-personal-deadline",
     *     operationId="instructor::SubmissionsController::actionSetPersonalDeadline",
     *     tags={"Instructor Student Files"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *        name="id",
     *        in="path",
     *        required=true,
     *        description="ID of the student file",
     *        @OA\Schema(ref="#/components/schemas/int_id"),
     *     ),
     *     @OA\Parameter(ref="#/components/parameters/yii2_fields"),
     *     @OA\Parameter(ref="#/components/parameters/yii2_expand"),
     *     @OA\RequestBody(
     *         description="personal deadline to be set",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(ref="#/components/schemas/Instructor_SubmissionResource_ScenarioPersonalDeadline"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="updated submission",
     *         @OA\JsonContent(ref="#/components/schemas/Instructor_SubmissionResource_Read"),
     *     ),
     *    @OA\Response(response=400, ref="#/components/responses/400"),
     *    @OA\Response(
     *        response=401,
     *        description="Unauthorized: missing, invalid or expired access or Canvas token. If Canvas login is required, the Proxy-Authenticate header containains the login URL.",
     *        @OA\JsonContent(ref="#/components/schemas/Yii2Error"),
     *    ),
     *    @OA\Response(response=403, ref="#/components/responses/403"),
     *    @OA\Response(response=404, ref="#/components/responses/404"),
     *    @OA\Response(response=422, ref="#/components/responses/422"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * ),
     */
    public function actionSetPersonalDeadline(int $id) {
        $submission = SubmissionResource::findOne($id);

        if (is_null($submission)) {
            throw new NotFoundHttpException(Yii::t('app', 'Submission not found'));
        }

        // Authorization check
        if (!Yii::$app->user->can('manageGroup', ['groupID' => $submission->task->groupID])) {
            throw new ForbiddenHttpException(Yii::t('app', 'You must be an instructor of the group to perform this action!'));
        }

        // Check semester
        if (SemesterResource::getActualID() !== $submission->task->semesterID) {
            throw new BadRequestHttpException(
                Yii::t('app', "You can't set a personal deadline for a solution from a previous semester!")
            );
        }

        $submission->scenario = SubmissionResource::SCENARIO_PERSONAL_DEADLINE;
        $submission->load(Yii::$app->request->post(), '');

        if (!$submission->validate()) {
            $this->response->statusCode = 422;
            return $submission->errors;
        }

        if (!$submission->save()) {
            throw new ServerErrorHttpException(Yii::t('app',  'Failed to save Submission. Message: ') . Yii::t('app', 'A database error occurred'));
        }

        if ($submission->isVersionControlled) {
            GitManager::updateSubmissionHooks($submission);
        }

        return $submission;
    }

    /**
     * Download a student file
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     *
     *  @OA\Get(
     *     path="/instructor/submissions/{id}/download",
     *     operationId="instructor::SubmissionsController::actionDownload",
     *     tags={"Instructor Student Files"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *        name="id",
     *        in="path",
     *        required=true,
     *        description="ID of the file",
     *        @OA\Schema(ref="#/components/schemas/int_id"),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="successful operation",
     *     ),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=403, ref="#/components/responses/403"),
     *    @OA\Response(response=404, ref="#/components/responses/404"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * ),
     */
    public function actionDownload(int $id): void
    {
        $submission = SubmissionResource::findOne($id);

        if (is_null($submission)) {
            throw new NotFoundHttpException(Yii::t('app', 'Submission not found'));
        }

        // Authorization check
        if (!Yii::$app->user->can('manageGroup', ['groupID' => $submission->task->groupID])) {
            throw new ForbiddenHttpException(Yii::t('app', 'You must be an instructor of the group to perform this action!'));
        }

        Yii::$app->response->sendFile(
            $submission->path,
            $submission->uploader->userCode . '.' . pathinfo($submission->name, PATHINFO_EXTENSION)
        );
    }


    /**
     * Download test report for student file
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     *
     *  @OA\Get(
     *     path="/instructor/submissions/{id}/downloadReport",
     *     operationId="instructor::SubmissionsController::actionDownloadReport",
     *     tags={"Instructor Student Files"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *        name="id",
     *        in="path",
     *        required=true,
     *        description="ID of the file",
     *        @OA\Schema(ref="#/components/schemas/int_id"),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="successful operation",
     *     ),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=403, ref="#/components/responses/403"),
     *    @OA\Response(response=404, ref="#/components/responses/404"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * ),
     */
    public function actionDownloadReport(int $id): void
    {
        $submission = SubmissionResource::findOne($id);

        if (is_null($submission)) {
            throw new NotFoundHttpException(Yii::t('app', 'Submission not found'));
        }

        // Authorization check
        if (!Yii::$app->user->can('manageGroup', ['groupID' => $submission->task->groupID])) {
            throw new ForbiddenHttpException(Yii::t('app', 'You must be an instructor of the group to perform this action!'));
        }

        if (!file_exists($submission->reportPath)) {
            throw new NotFoundHttpException(Yii::t('app', 'Test reports not exist for this student file.'));
        }

        Yii::$app->response->sendFile(
            $submission->reportPath,
            $submission->uploader->userCode . '_report.tar'
        );
    }

    /**
     * Send all solutions of the task zipped to the client
     * @param int $taskID is the id of the task
     * @param boolean $onlyUngraded select only ungraded solutions to be downloaded
     * @throws BadRequestHttpException
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     * @throws \yii\base\Exception
     *
     *  @OA\Get(
     *     path="/instructor/submissions/download-all-files",
     *     operationId="instructor::SubmissionsController::actionDownloadAllFiles",
     *     tags={"Instructor Student Files"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *        name="taskID",
     *        in="query",
     *        required=true,
     *        description="ID of the task",
     *        explode=true,
     *        @OA\Schema(ref="#/components/schemas/int_id"),
     *     ),
     *     @OA\Parameter(
     *        name="onlyUngraded",
     *        in="query",
     *        required=false,
     *        description="Collect unrgraded solutions only. Optional parameter, the default value is false",
     *        explode=true,
     *        @OA\Schema(type="boolean"),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="successful operation",
     *     ),
     *    @OA\Response(response=400, ref="#/components/responses/400"),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=403, ref="#/components/responses/403"),
     *    @OA\Response(response=404, ref="#/components/responses/404"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * ),
     */
    public function actionDownloadAllFiles(int $taskID, bool $onlyUngraded = false): void
    {
        $task = TaskResource::findOne($taskID);

        if (is_null($task)) {
            throw new NotFoundHttpException(Yii::t('app', 'Task not found'));
        }

        // Authorization check
        if (!Yii::$app->user->can('manageGroup', ['groupID' => $task->groupID])) {
            throw new ForbiddenHttpException(Yii::t('app', 'You must be an instructor of the group to perform this action!'));
        }

        $zipName = Yii::$app->security->generateRandomString(36) . '.zip';
        $zipPath = Yii::getAlias("@tmp/instructor/$zipName");
        $zipFolder = Yii::getAlias("@tmp/instructor/");

        if (!file_exists($zipFolder)) {
            FileHelper::createDirectory($zipFolder, 0755, true);
        }

        if ($onlyUngraded) {
            $files = SubmissionResource::findAll(
                [
                    'taskID' => $taskID,
                    'status' => [
                        Submission::STATUS_UPLOADED,
                        Submission::STATUS_PASSED,
                        Submission::STATUS_FAILED,
                    ]
                ]
            );
        } else {
            /** @var SubmissionResource[] $files */
            $files = SubmissionResource::find()
                ->andWhere(['taskID' => $taskID])
                ->andWhere(['not', ['status' => Submission::STATUS_NO_SUBMISSION]])
                ->all();
        }

        if (count($files) < 1) {
            throw new BadRequestHttpException(Yii::t('app', 'Files not found'));
        }

        $zip = new \ZipArchive();
        $zip->open($zipPath, \ZIPARCHIVE::CREATE | \ZIPARCHIVE::OVERWRITE);

        foreach ($files as $file) {
            $userCode = $file->uploader->userCode;
            $zip->addFile($file->path, $userCode . '.zip');
        }
        $zip->close();

        Yii::$app->response->sendFile($zipPath, $task->name . '-' . $task->groupID . '.zip')->on(\yii\web\Response::EVENT_AFTER_SEND, function ($event) {
           unlink($event->data);
        }, $zipPath);
    }

    /**
     * Start a CodeCompass container
     *
     * @throws ConflictHttpException
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     * @throws ServerErrorHttpException
     * @throws StaleObjectException
     *
     * @OA\POST(
     *     path="/instructor/submissions/{id}/start-code-compass",
     *     operationId="instructor::SubmissionsController::actionStartCodeCompass",
     *     tags={"Instructor Student Files"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *        name="id",
     *        in="path",
     *        required=true,
     *        description="ID of the file",
     *        @OA\Schema(ref="#/components/schemas/int_id"),
     *     ),
     *     @OA\Parameter(ref="#/components/parameters/yii2_fields"),
     *     @OA\Parameter(ref="#/components/parameters/yii2_expand"),
     *     @OA\Response(
     *         response=200,
     *         description="successful operation",
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="successful creation of start request",
     *     ),
     *    @OA\Response(response=403, ref="#/components/responses/403"),
     *    @OA\Response(response=404, ref="#/components/responses/404"),
     *    @OA\Response(response=409, ref="#/components/responses/404"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * ),
     */
    public function actionStartCodeCompass(int $id): SubmissionResource
    {
        if(!CodeCompassHelper::isCodeCompassIntegrationEnabled()) {
            throw new ForbiddenHttpException(
                Yii::t('app', 'CodeCompass is not enabled.'));
        }

        $submission = SubmissionResource::findOne($id);
        if (is_null($submission)) {
            throw new NotFoundHttpException(
                Yii::t('app', 'File not found.'));
        }

        if (!Yii::$app->user->can('manageGroup', ['groupID' => $submission->task->groupID])) {
            throw new ForbiddenHttpException(
                Yii::t('app', 'You must be an instructor of the group to perform this action!'));
        }

        if(CodeCompassHelper::isTooManyContainersRunning()) {
            Yii::$app->response->statusCode = 201;
            $codeCompassInstance = new CodeCompassInstanceResource();
            $codeCompassInstance->submissionId = $id;
            $codeCompassInstance->instanceStarterUserId = Yii::$app->user->id;
            $codeCompassInstance->status = CodeCompassInstance::STATUS_WAITING;
            $codeCompassInstance->creationTime = date('Y-m-d H:i:s');
            $codeCompassInstance->save(false);

            return $submission;
        }

        if(CodeCompassHelper::isContainerAlreadyRunning($id)) {
            throw new ConflictHttpException(
                Yii::t('app', 'CodeCompass is already running.'));
        }

        if(CodeCompassHelper::isContainerCurrentlyStarting($id)) {
            throw new ConflictHttpException(
                Yii::t('app', 'CodeCompass is already starting.'));
        }

        $selectedPort = CodeCompassHelper::selectFirstAvailablePort();
        if(is_null($selectedPort)) {
            throw new ConflictHttpException(
                Yii::t('app', 'There is no port available to run the CodeCompass on!'));
        }

        $docker = CodeCompassHelper::createDockerClient();
        $taskId = $submission->taskID;

        $codeCompass = new CodeCompass(
            $submission,
            $docker,
            $selectedPort,
            CodeCompassHelper::getCachedImageNameForTask($taskId, $docker)
        );

        $codeCompassInstance = new CodeCompassInstanceResource();
        $codeCompassInstance->submissionId = $id;
        $codeCompassInstance->containerId = $codeCompass->containerId;
        $codeCompassInstance->status = CodeCompassInstance::STATUS_STARTING;
        $codeCompassInstance->port = (int) $selectedPort;
        $codeCompassInstance->instanceStarterUserId = Yii::$app->user->id;
        $codeCompassInstance->creationTime = date('Y-m-d H:i:s');
        $codeCompassInstance->save(false);

        try {
            $codeCompass->start();
        } catch (\Exception $ex) {
            $codeCompassInstance->delete();
            throw new ServerErrorHttpException(
                Yii::t('app', 'An error occurred while starting CodeCompass.'));
        }

        $codeCompassInstance->status = CodeCompassInstance::STATUS_RUNNING;
        $codeCompassInstance->errorLogs = $codeCompass->errorLogs;
        $codeCompassInstance->username = $codeCompass->codeCompassUsername;
        $codeCompassInstance->password = $codeCompass->codeCompassPassword;
        $submission->task->save(false);
        $codeCompassInstance->save(false);

        return $submission;
    }

    /**
     * Stops a CodeCompass container
     *
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     * @throws ServerErrorHttpException
     * @throws UnauthorizedHttpException
     *
     * @OA\POST(
     *     path="/instructor/submissions/{id}/stop-code-compass",
     *     operationId="instructor::SubmissionsController::actionStopCodeCompass",
     *     tags={"Instructor Student Files"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *        name="id",
     *        in="path",
     *        required=true,
     *        description="ID of the file",
     *        @OA\Schema(ref="#/components/schemas/int_id"),
     *     ),
     *     @OA\Parameter(ref="#/components/parameters/yii2_fields"),
     *     @OA\Parameter(ref="#/components/parameters/yii2_expand"),
     *     @OA\Response(
     *         response=200,
     *         description="successful operation",
     *     ),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=403, ref="#/components/responses/403"),
     *    @OA\Response(response=404, ref="#/components/responses/404"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * ),
     */
    public function actionStopCodeCompass(int $id): SubmissionResource
    {
        if(!CodeCompassHelper::isCodeCompassIntegrationEnabled()) {
            throw new ForbiddenHttpException(
                Yii::t('app', 'CodeCompass is not enabled.'));
        }

        $submission = SubmissionResource::findOne($id);
        if (is_null($submission)) {
            throw new NotFoundHttpException(
                Yii::t('app', 'File not found.'));
        }

        $codeCompassInstance = CodeCompassInstance::find()->findRunningForSubmissionId($id)->one();
        if (is_null($codeCompassInstance)) {
            throw new NotFoundHttpException(
                Yii::t('app', 'CodeCompass is not running for this solution.'));
        }

        if (!Yii::$app->user->can('manageGroup', ['groupID' => $submission->task->groupID])) {
            throw new UnauthorizedHttpException(
                Yii::t('app', 'You must be an instructor of the group to perform this action!'));
        }

        $codeCompass = new CodeCompass(
                $submission,
                CodeCompassHelper::createDockerClient(),
                $codeCompassInstance->port
        );

        try {
            $codeCompass->stop();
        } catch (\Exception $ex) {
            throw new ServerErrorHttpException(
                Yii::t('app', 'An error occurred while stopping CodeCompass.'));
        }

        try {
            $codeCompassInstance->delete();
            return $submission;
        } catch (\Exception $e) {
            throw new ServerErrorHttpException(Yii::t('app', 'A database error occurred'));
        }
    }

    /**
     * Get information about an uploaded file
     * @return AutoTesterResultResource[]
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     *
     * @OA\Get(
     *     path="/instructor/submissions/{id}/auto-tester-results",
     *     operationId="instructor::SubmissionsController::actionAutoTesterResults",
     *     tags={"Instructor Student Files"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(ref="#/components/parameters/yii2_fields"),
     *     @OA\Parameter(ref="#/components/parameters/yii2_expand"),
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the student file",
     *         @OA\Schema(ref="#/components/schemas/int_id")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="successful operation",
     *         @OA\JsonContent(ref="#/components/schemas/Common_AutoTesterResultResource_Read"),
     *     ),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=403, ref="#/components/responses/403"),
     *    @OA\Response(response=404, ref="#/components/responses/404"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * ),
     */
    public function actionAutoTesterResults(int $id): array
    {
        $submission = SubmissionResource::findOne($id);

        if (is_null($submission)) {
            throw new NotFoundHttpException(Yii::t('app', 'Submission not found'));
        }

        // Authorization check
        if (!Yii::$app->user->can('manageGroup', ['groupID' => $submission->task->groupID])) {
            throw new ForbiddenHttpException(Yii::t('app', 'You must be an instructor of the group to perform this action!'));
        }

        $results = $submission->testResults;

        $idx = 1;
        return array_map(function ($result) use (&$idx) {
            return new AutoTesterResultResource(
                $idx++,
                $result->isPassed,
                $result->errorMsg
            );
        }, $results);
    }

    /**
     * Get information about used IP addresses
     * @return IpAddressResource[]
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     *
     * @OA\Get(
     *     path="/instructor/submissions/{id}/ip-addresses",
     *     operationId="instructor::SubmissionsController::actionIpAddresses",
     *     tags={"Instructor Student Files"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(ref="#/components/parameters/yii2_fields"),
     *     @OA\Parameter(ref="#/components/parameters/yii2_expand"),
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the submission",
     *         @OA\Schema(ref="#/components/schemas/int_id")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="successful operation",
     *         @OA\JsonContent(ref="#/components/schemas/Instructor_IpAddressResource_Read"),
     *     ),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=403, ref="#/components/responses/403"),
     *    @OA\Response(response=404, ref="#/components/responses/404"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * ),
     */
    public function actionIpAddresses(int $id): array
    {
        $submission = Submission::findOne($id);

        if (is_null($submission)) {
            throw new NotFoundHttpException(Yii::t('app', Yii::t('app', 'Submission not found')));
        }

        // Authorization check
        if (!Yii::$app->user->can('manageGroup', ['groupID' => $submission->task->groupID])) {
            throw new ForbiddenHttpException(Yii::t('app', 'You must be an instructor of the group to perform this action!'));
        }

        $ipAddresses = array_map(function($ipAddress): IpAddressResource {
            return new IpAddressResource($ipAddress);
        }, $submission->detailedIpAddresses);
        return $ipAddresses;
    }

    /**
     * Returns a signed JWT for a submission.
     *
     * The payload of the JWT contains the student ID and the submission ID.
     *
     * @throws NotFoundHttpException
     * @throws ForbiddenHttpException
     *
     * @OA\Post(
     *     path="/instructor/submissions/{id}/jwt",
     *     operationId="instructor::SubmissionsController::actionJwtGenerate",
     *     tags={"Instructor Student Files"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         description="Submission data to generate a JWT",
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"id"},
     *             @OA\Property(
     *                 property="id",
     *                 type="integer",
     *                 description="ID of the submission",
     *                 example=123
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="JWT successfully signed.",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(ref="#/components/schemas/Common_JwtResource_Read")
     *          ),
     *    ),
     *    @OA\Response(response=400, ref="#/components/responses/400"),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=404, ref="#/components/responses/404"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * )
     */
    public function actionJwtGenerate(int $id): JwtResource
    {
        $submission = SubmissionResource::findOne($id);

        if (is_null($submission)) {
            throw new NotFoundHttpException(Yii::t('app', 'Submission not found'));
        }

        // Authorization check
        if (!Yii::$app->user->can('manageGroup', ['groupID' => $submission->task->groupID])) {
            throw new ForbiddenHttpException(Yii::t('app', 'You must be an instructor of the group to perform this action!'));
        }

        $studentId = $submission->uploaderID;
        $tokenData = ['studentId' => $studentId, 'submissionId' => $id];

        $jwtResource = new JwtResource();
        $jwtResource->token = JwtHelper::create($tokenData);

        return $jwtResource;
    }

    /**
     * Validate a JWT for submission.
     * @throws BadRequestHttpException
     *
     * @OA\Get(
     *     path="/instructor/submissions/jwt-validate",
     *     operationId="instructor::SubmissionsController::actionJwtValidate",
     *     tags={"Instructor Student Files"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="token",
     *         in="query",
     *         required=true,
     *         description="JWT token to validate.",
     *         @OA\Schema(type="string")
     *     ),
     *  @OA\Response(
     *         response=200,
     *         description="JWT validation result.",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(ref="#/components/schemas/Instructor_JwtValidationResource_Read")
     *          ),
     *     ),
     *     @OA\Response(response=400, ref="#/components/responses/400"),
     *     @OA\Response(response=401, ref="#/components/responses/401"),
     *     @OA\Response(response=500, ref="#/components/responses/500"),
     * )
     */
    public function actionJwtValidate(string $token): JwtValidationResource
    {
        $response = new JwtValidationResource();
        $response->payload = [];

        if (empty($token)) {
            throw new BadRequestHttpException(Yii::t('app', 'Missing token'));
        }

        try {
            $payload = JwtHelper::validate($token);

            $response->success = true;
            $response->payload = $payload;
            $response->message = Yii::t('app', 'JWT is valid.');
            return $response;
        } catch (\Exception $e) {
            $response->success = false;
            $response->message = Yii::t('app', 'Invalid JWT: ') . $e->getMessage();
            return $response;
        }
    }
}
