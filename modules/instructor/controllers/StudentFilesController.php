<?php

namespace app\modules\instructor\controllers;

use app\components\CanvasIntegration;
use app\components\GitManager;
use app\models\StudentFile;
use app\models\User;
use app\modules\instructor\resources\GroupResource;
use app\resources\SemesterResource;
use Yii;
use app\modules\instructor\resources\StudentFileResource;
use app\modules\instructor\resources\TaskResource;
use app\resources\UserResource;
use yii\data\ActiveDataProvider;
use yii\data\BaseDataProvider;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;
use yii2tech\spreadsheet\Spreadsheet;
use yii2tech\csvgrid\CsvGrid;

/**
 * This class provides access to student files for instructors
 */
class StudentFilesController extends BaseInstructorRestController
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
            'download' => ['GET'],
            'download-all-files' => ['GET']
        ]);
    }

    /**
     * List student files for a task
     * @param int $taskID
     * @return ActiveDataProvider
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     *
     * @OA\Get(
     *     path="/instructor/student-files/list-for-task",
     *     operationId="instructor::StudentFilesController::actionListForTask",
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
     *        @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Instructor_StudentFileResource_Read")),
     *    ),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=403, ref="#/components/responses/403"),
     *    @OA\Response(response=404, ref="#/components/responses/404"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * ),
     */
    public function actionListForTask($taskID)
    {
        $task = TaskResource::findOne($taskID);

        if (is_null($task)) {
            throw new NotFoundHttpException(Yii::t('app', 'Task not found'));
        }

        // Authorization check
        if (!Yii::$app->user->can('manageGroup', ['groupID' => $task->groupID])) {
            throw new ForbiddenHttpException(Yii::t('app', 'You must be an instructor of the group to perform this action!'));
        }

        $query = StudentFileResource::find()
            ->where(['taskID' => $taskID]);

        return new ActiveDataProvider(
            [
              'query' => $query,
              'pagination' => false
            ]
        );
    }

    /**
     * List student files for a task, then export the list to a spreadsheet
     * @param int $taskID
     * @param string $format
     * @return \yii\web\Response
     * @throws BadRequestHttpException
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     *
     *  @OA\Get(
     *     path="/instructor/student-files/export-spreadsheet",
     *     operationId="instructor::StudentFilesController::actionExportSpreadsheet",
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
     *        @OA\Schema(type="string", enum={"xls", "csv"}),
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
    public function actionExportSpreadsheet($taskID, $format)
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
                'query' => StudentFile::find()->where(['taskID' => $taskID]),
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
                'header' => 'NEPTUN',
                'attribute' => 'uploader.neptun',
            ],
            [
                'header' => Yii::t('app', 'Upload Time'),
                'attribute' => 'uploadTime',
            ],
            [
                'header' => Yii::t('app', 'Is Accepted'),
                'attribute' => 'translatedIsAccepted',
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
        ];

        if ($format == 'xls') {
            return $this->exportToXls($task->name, $dataProvider, $columns);
        } elseif ($format == 'csv') {
            return $this->exportToCsv($task->name, $dataProvider, $columns);
        } else {
            throw new BadRequestHttpException(Yii::t('app', 'Unsupported file format'));
        }
    }

    /**
     * Creates a xls file from the given DataProvider
     * @param string $name
     * @param BaseDataProvider $dataProvider
     * @param array $columns
     * @return \yii\web\Response
     */
    private function exportToXls($name, $dataProvider, $columns)
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
     * Creates a cvs file from the given DataProvider
     * @param string $name
     * @param BaseDataProvider $dataProvider
     * @param array $columns
     * @return \yii\web\Response
     */
    private function exportToCsv($name, $dataProvider, $columns)
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
     * @param int $groupID
     * @param int $uploaderID
     * @return ActiveDataProvider
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     *
     * @OA\Get(
     *     path="/instructor/student-files/list-for-student",
     *     operationId="instructor::StudentFilesController::actionListForStudent",
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
     *        @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Instructor_StudentFileResource_Read")),
     *    ),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=403, ref="#/components/responses/403"),
     *    @OA\Response(response=404, ref="#/components/responses/404"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * ),
     */
    public function actionListForStudent($groupID, $uploaderID)
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

        $query = StudentFileResource::find()
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
     * @param int $id
     * @return StudentFileResource
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     *
     * @OA\Get(
     *     path="/instructor/student-files/{id}",
     *     operationId="instructor::StudentFilesController::actionView",
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
     *         @OA\JsonContent(ref="#/components/schemas/Instructor_StudentFileResource_Read"),
     *     ),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=403, ref="#/components/responses/403"),
     *    @OA\Response(response=404, ref="#/components/responses/404"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * ),
     */
    public function actionView($id)
    {
        $studentFile = StudentFileResource::findOne($id);

        if (is_null($studentFile)) {
            throw new NotFoundHttpException(Yii::t('app', 'StudentFile not found'));
        }

        // Authorization check
        if (!Yii::$app->user->can('manageGroup', ['groupID' => $studentFile->task->groupID])) {
            throw new ForbiddenHttpException(Yii::t('app', 'You must be an instructor of the group to perform this action!'));
        }

        return $studentFile;
    }

    /**
     * Grade solution (update student file)
     * @param int $id
     * @return StudentFileResource|array
     * @throws BadRequestHttpException
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     * @throws ServerErrorHttpException
     *
     * @OA\Patch(
     *     path="/instructor/student-files/{id}",
     *     operationId="instructor::StudentFilesController::actionUpdate",
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
     *             @OA\Schema(ref="#/components/schemas/Instructor_StudentFileResource_ScenarioGrade"),
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
    public function actionUpdate($id)
    {
        $studentFile = StudentFileResource::findOne($id);

        if (is_null($studentFile)) {
            throw new NotFoundHttpException(Yii::t('app', 'StudentFile not found'));
        }

        // Authorization check
        if (!Yii::$app->user->can('manageGroup', ['groupID' => $studentFile->task->groupID])) {
            throw new ForbiddenHttpException(Yii::t('app', 'You must be an instructor of the group to perform this action!'));
        }

        // Check semester
        if (SemesterResource::getActualID() !== $studentFile->task->semesterID) {
            throw new BadRequestHttpException(
                Yii::t('app', "You can't modify grade a solution from a previous semester!")
            );
        }

        $studentFile->scenario = StudentFileResource::SCENARIO_GRADE;
        $studentFile->load(Yii::$app->request->post(), '');
        $studentFile->graderID = Yii::$app->user->id;
        if (!$studentFile->validate()) {
            $this->response->statusCode = 422;
            return $studentFile->errors;
        }

        // Disable Git push if submission was accepted
        if (Yii::$app->params['versionControl']['enabled'] && $studentFile->task->isVersionControlled) {
            GitManager::afterStatusUpdate($studentFile);
        }

        // Upload to the canvas if synchronized
        if (Yii::$app->params['canvas']['enabled'] && !empty($studentFile->canvasID)) {
            $user = User::findIdentity(Yii::$app->user->id);
            if (!$user->isAuthenticatedInCanvas) {
                $this->response->statusCode = 401;
                $this->response->headers->add('Proxy-Authenticate', CanvasIntegration::getLoginURL());
                return null;
            }
        }

        if (!$studentFile->save()) {
            throw new ServerErrorHttpException(Yii::t('app',  'Failed to save StudentFile. Message: ') . Yii::t('app', 'A database error occurred'));
        }

        // Log
        Yii::info(
            "Solution #$studentFile->id graded" .
            "for task {$studentFile->task->name} (#$studentFile->taskID) " .
            "with status $studentFile->isAccepted, grade $studentFile->grade and notes: $studentFile->notes",
            __METHOD__
        );


        // E-mail notification
        if ($studentFile->uploader->notificationEmail) {
            $originalLanguage = Yii::$app->language;
            Yii::$app->language = $studentFile->uploader->locale;
            Yii::$app->mailer->compose('student/markSolution', [
                'studentFile' => $studentFile,
                'actor' => Yii::$app->user->identity,
            ])
                ->setFrom(Yii::$app->params['systemEmail'])
                ->setTo($studentFile->uploader->notificationEmail)
                ->setSubject(Yii::t('app/mail', 'Graded submission'))
                ->send();
            Yii::$app->language = $originalLanguage;
        }

        // Upload to the canvas if synchronized
        if (Yii::$app->params['canvas']['enabled'] && !empty($studentFile->canvasID)) {
            $canvas = new CanvasIntegration();
            if ($canvas->refreshCanvasToken($user)) {
                $canvas->uploadGradeToCanvas($studentFile->id);
            } else {
                throw new ServerErrorHttpException(Yii::t('app', 'Failed to refresh Canvas Token.'));
            }
        }

        return $studentFile;
    }

    /**
     * Download a student file
     * @param int $id
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     *
     *  @OA\Get(
     *     path="/instructor/student-files/{id}/download",
     *     operationId="instructor::StudentFilesController::actionDownload",
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
    public function actionDownload($id)
    {
        $studentFile = StudentFileResource::findOne($id);

        if (is_null($studentFile)) {
            throw new NotFoundHttpException(Yii::t('app', 'StudentFile not found'));
        }

        // Authorization check
        if (!Yii::$app->user->can('manageGroup', ['groupID' => $studentFile->task->groupID])) {
            throw new ForbiddenHttpException(Yii::t('app', 'You must be an instructor of the group to perform this action!'));
        }

        Yii::$app->response->sendFile(
            $studentFile->path,
            $studentFile->uploader->neptun . '.' . pathinfo($studentFile->name, PATHINFO_EXTENSION)
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
     *     path="/instructor/student-files/download-all-files",
     *     operationId="instructor::StudentFilesController::actionDownloadAllFiles",
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
    public function actionDownloadAllFiles($taskID, $onlyUngraded = false)
    {
        $onlyUngraded = filter_var($onlyUngraded, FILTER_VALIDATE_BOOLEAN);

        $task = TaskResource::findOne($taskID);

        if (is_null($task)) {
            throw new NotFoundHttpException(Yii::t('app', 'Task not found'));
        }

        // Authorization check
        if (!Yii::$app->user->can('manageGroup', ['groupID' => $task->groupID])) {
            throw new ForbiddenHttpException(Yii::t('app', 'You must be an instructor of the group to perform this action!'));
        }

        $zipName = Yii::$app->security->generateRandomString(36) . '.zip';
        $zipPath = Yii::$app->basePath . '/' . Yii::$app->params['data_dir'] . '/tmp/instructor/' . $zipName;
        $zipFolder = Yii::$app->basePath . '/' . Yii::$app->params['data_dir'] . '/tmp/instructor/';

        if (!file_exists($zipFolder)) {
            mkdir($zipFolder, 0755, true);
        }

        if ($onlyUngraded) {
            $files = StudentFileResource::findAll(['taskID' => $taskID, 'isAccepted' => ['Uploaded', 'Updated', 'Passed', 'Failed']]);
        } else {
            $files = StudentFileResource::findAll(['taskID' => $taskID]);
        }

        if (count($files) < 1) {
            throw new BadRequestHttpException(Yii::t('app', 'Files not found'));
        }

        $zip = new \ZipArchive();
        $zip->open($zipPath, \ZIPARCHIVE::CREATE | \ZIPARCHIVE::OVERWRITE);

        foreach ($files as $file) {
            $neptun = $file->uploader->neptun;
            $zip->addFile($file->path, $neptun . '.zip');
        }
        $zip->close();

        Yii::$app->response->sendFile($zipPath, $task->name . '-' . $task->groupID . '.zip')->on(\yii\web\Response::EVENT_AFTER_SEND, function ($event) {
           unlink($event->data);
        }, $zipPath);
    }
}
