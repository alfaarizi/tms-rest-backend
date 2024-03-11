<?php

namespace app\modules\instructor\controllers;

use app\components\CodeCompassHelper;
use app\components\GitManager;
use app\models\Group;
use app\models\InstructorFile;
use app\models\StudentFile;
use app\models\Subscription;
use app\models\TestCase;
use app\modules\instructor\resources\GroupResource;
use app\modules\instructor\resources\SetupCodeCompassParserResource;
use app\modules\instructor\resources\TaskResource;
use app\resources\SemesterResource;
use app\resources\UserResource;
use Docker\API\Exception\ImageDeleteConflictException;
use Yii;
use yii\base\ErrorException;
use yii\data\ActiveDataProvider;
use yii\data\ArrayDataProvider;
use yii\web\BadRequestHttpException;
use yii\web\ConflictHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;


/**
 * @OA\PathItem(
 *   path="/instructor/tasks/{id}",
 *   @OA\Parameter(
 *      name="id",
 *      in="path",
 *      required=true,
 *      description="ID of the task",
 *      @OA\Schema(ref="#/components/schemas/int_id")
 *   ),
 * )
 */

/**
 * This class provides access to tasks for instructors
 */
class TasksController extends BaseInstructorRestController
{
    /**
     * @inheritdoc
     */
    protected function verbs()
    {
        return array_merge(
            parent::verbs(),
            [
                'index' => ['GET'],
                'view' => ['GET'],
                'create' => ['POST'],
                'delete' => ['DELETE'],
                'update' => ['PATCH', 'PUT'],
                'list-for-course' => ['GET'],
                'list-for-users' => ['POST'],
                'setup-code-compass-parser' => ['POST']
            ]
        );
    }

    /**
     * List tasks for the given group
     * @return ActiveDataProvider[]
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     *
     * @OA\Get(
     *     path="/instructor/tasks",
     *     operationId="instructor::TasksController::actionIndex",
     *     tags={"Instructor Tasks"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="groupID",
     *         in="query",
     *         required=true,
     *         description="ID of the group",
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
     *        @OA\JsonContent(
     *          type="array",
     *          @OA\Items(type="array", @OA\Items(ref="#/components/schemas/Instructor_TaskResource_Read"))
     *        ),
     *    ),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=403, ref="#/components/responses/403"),
     *    @OA\Response(response=404, ref="#/components/responses/404"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * )
     */
    public function actionIndex(int $groupID): array
    {
        $group = GroupResource::findOne($groupID);

        if (is_null($group)) {
            throw new NotFoundHttpException('Group not found');
        }

        // Authorization check
        if (!Yii::$app->user->can('manageGroup', ['groupID' => $groupID])) {
            throw new ForbiddenHttpException(
                Yii::t('app', 'You must be an instructor of the group to perform this action!')
            );
        }

        $categories = TaskResource::listCategories($group);

        $dataProviders = [];
        foreach ($categories as $category) {
            $query = $group
                ->getTasks()
                ->andWhere(['category' => $category]);

            $dataProviders[] = new ActiveDataProvider(
                [
                    'query' => $query,
                    'pagination' => false
                ]
            );
        }
        return $dataProviders;
    }

    /**
     * View Task
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     *
     * @OA\Get(
     *     path="/instructor/tasks/{id}",
     *     operationId="instructor::TasksController::actionView",
     *     tags={"Instructor Tasks"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(ref="#/components/parameters/yii2_fields"),
     *     @OA\Parameter(ref="#/components/parameters/yii2_expand"),
     *     @OA\Response(
     *         response=200,
     *         description="successful operation",
     *         @OA\JsonContent(ref="#/components/schemas/Instructor_TaskResource_Read"),
     *     ),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=403, ref="#/components/responses/403"),
     *    @OA\Response(response=404, ref="#/components/responses/404"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * )
     */
    public function actionView(int $id): TaskResource
    {
        $task = TaskResource::findOne($id);

        if (is_null($task)) {
            throw new NotFoundHttpException(Yii::t('app', 'Task not found.'));
        }

        // Authorization check
        if (!Yii::$app->user->can('manageGroup', ['groupID' => $task->groupID])) {
            throw new ForbiddenHttpException(
                Yii::t('app', 'You must be an instructor of the group to perform this action!')
            );
        }

        return $task;
    }

    /**
     * Create a new task
     * @return TaskResource|array
     * @throws ForbiddenHttpException
     * @throws ServerErrorHttpException
     * @throws BadRequestHttpException
     *
     * @OA\Post(
     *     path="/instructor/tasks",
     *     operationId="instructor::TasksController::actionCreate",
     *     tags={"Instructor Tasks"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(ref="#/components/parameters/yii2_fields"),
     *     @OA\Parameter(ref="#/components/parameters/yii2_expand"),
     *     @OA\RequestBody(
     *         description="new task",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(ref="#/components/schemas/Instructor_TaskResource_ScenarioCreate"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="new task created",
     *         @OA\JsonContent(ref="#/components/schemas/Instructor_TaskResource_Read"),
     *     ),
     *    @OA\Response(response=400, ref="#/components/responses/400"),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=403, ref="#/components/responses/403"),
     *    @OA\Response(response=422, ref="#/components/responses/422"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * )
     */
    public function actionCreate()
    {
        $task = new TaskResource();
        $task->scenario = TaskResource::SCENARIO_CREATE;
        $task->load(Yii::$app->request->post(), '');
        $task->createrID = Yii::$app->user->id;

        if (!$task->validate()) {
            $this->response->statusCode = 422;
            return $task->errors;
        }

        if ($task->isVersionControlled && !Yii::$app->params['versionControl']['enabled']) {
            throw new BadRequestHttpException(
                Yii::t('app', 'Version control is disabled. Contact the administrator for more information.')
            );
        }

        $task->semesterID = $task->group->semesterID;

        // Check semester
        if ($task->semesterID !== SemesterResource::getActualID()) {
            throw new BadRequestHttpException(
                Yii::t('app', "You can't modify a group from a previous semester!")
            );
        }

        // Authorization check
        if (!Yii::$app->user->can('manageGroup', ['groupID' => $task->groupID])) {
            throw new ForbiddenHttpException(
                Yii::t('app', 'You must be an instructor of the group to perform this action!')
            );
        }

        if (!$task->save(false)) {
            throw new ServerErrorHttpException(
                Yii::t('app', 'Failed to save task. Message: ') . Yii::t('app', 'A database error occurred')
            );
        }

        // Create new StudentFile for everybody in the group
        foreach ($task->group->subscriptions as $subscription) {
            $studentFile = new StudentFile();
            $studentFile->taskID = $task->id;
            $studentFile->isAccepted = StudentFile::IS_ACCEPTED_NO_SUBMISSION;
            $studentFile->autoTesterStatus = StudentFile::AUTO_TESTER_STATUS_NOT_TESTED;
            $studentFile->uploaderID = $subscription->userID;
            $studentFile->name = null;
            $studentFile->grade = null;
            $studentFile->notes = "";
            $studentFile->uploadTime = null;
            $studentFile->isVersionControlled = $task->isVersionControlled;
            $studentFile->uploadCount = 0;
            $studentFile->verified = true;
            $studentFile->codeCheckerResultID = null;

            if ($studentFile->save()) {
                Yii::info(
                    "A new blank solution has been uploaded for " .
                    "{$studentFile->task->name} ($studentFile->taskID)",
                    __METHOD__
                );
                $this->response->statusCode = 201;
            } elseif ($studentFile->hasErrors()) {
                $this->response->statusCode = 422;
                return $studentFile->errors;
            } else {
                $this->response->statusCode = 500;
                throw new ServerErrorHttpException(Yii::t('app', "A database error occurred"));
            }
        }

        // Email notifications
        $messages = [];

        $originalLanguage = Yii::$app->language;
        foreach ($task->group->subscriptions as $subscription) {
            if (!empty($subscription->user->notificationEmail)) {
                Yii::$app->language = $subscription->user->locale;
                $messages[] = Yii::$app->mailer->compose(
                    'student/newTask',
                    [
                        'task' => $task,
                        'actor' => Yii::$app->user->identity,
                    ]
                )
                    ->setFrom(Yii::$app->params['systemEmail'])
                    ->setTo($subscription->user->notificationEmail)
                    ->setSubject(Yii::t('app/mail', 'New task'));
            }
        }
        Yii::$app->language = $originalLanguage;

        // Send mass email notifications
        Yii::$app->mailer->sendMultiple($messages);

        Yii::info(
            "A new task $task->name (id: $task->id) has been created " .
            "for {$task->group->course->name} ({$task->group->number})",
            __METHOD__
        );

        $this->response->statusCode = 201;
        return $task;
    }

    /**
     * Update a task
     * @return TaskResource|array
     * @throws BadRequestHttpException
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     * @throws ServerErrorHttpException
     *
     * @OA\Put(
     *     path="/instructor/tasks/{id}",
     *     operationId="instructor::TasksController::actionUpdate",
     *     tags={"Instructor Tasks"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(ref="#/components/parameters/yii2_fields"),
     *     @OA\Parameter(ref="#/components/parameters/yii2_expand"),
     *     @OA\RequestBody(
     *         description="updated task",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(ref="#/components/schemas/Instructor_TaskResource_ScenarioUpdate"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="task updated",
     *         @OA\JsonContent(ref="#/components/schemas/Instructor_TaskResource_Read"),
     *     ),
     *    @OA\Response(response=400, ref="#/components/responses/400"),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=403, ref="#/components/responses/403"),
     *    @OA\Response(response=404, ref="#/components/responses/404"),
     *    @OA\Response(response=422, ref="#/components/responses/422"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * )
     */
    public function actionUpdate(int $id)
    {
        // Get the task.
        $task = TaskResource::findOne($id);

        if (is_null($task)) {
            throw new NotFoundHttpException(Yii::t('app', 'Task not found.'));
        }
        $task->scenario = TaskResource::SCENARIO_UPDATE;


        // Authorization check
        if (!Yii::$app->user->can('manageGroup', ['groupID' => $task->groupID])) {
            throw new ForbiddenHttpException(
                Yii::t('app', 'You must be an instructor of the group to perform this action!')
            );
        }

        // Check semester
        if ($task->semesterID !== SemesterResource::getActualID()) {
            throw new BadRequestHttpException(
                Yii::t('app', "You can't modify a task from a previous semester!")
            );
        }

        // Canvas synchronization check
        if ($task->group->isCanvasCourse) {
            throw new BadRequestHttpException(
                Yii::t('app', 'This operation cannot be performed on a canvas synchronized course!')
            );
        }

        $oldAvailable = $task->available;
        $oldSoftDeadLine = $task->softDeadline;
        $oldHardDeadLine = $task->hardDeadline;
        $task->load(Yii::$app->request->post(), '');

        if (!$task->validate()) {
            $this->response->statusCode = 422;
            return $task->errors;
        }

        if (!$task->save(false)) {
            throw new ServerErrorHttpException(
                Yii::t('app', 'Failed to save task. Message: ') . Yii::t('app', 'A database error occurred')
            );
        }

        // Email notifications if deadline changed
        if (
            $task->available != $oldAvailable ||
            $task->softDeadline != $oldSoftDeadLine ||
            $task->hardDeadline != $oldHardDeadLine
        ) {
            $messages = [];
            $group = GroupResource::findOne($task->groupID);

            $originalLanguage = Yii::$app->language;
            foreach ($task->group->subscriptions as $subscription) {
                if (!empty($subscription->user->notificationEmail)) {
                    Yii::$app->language = $subscription->user->locale;
                    $messages[] = Yii::$app->mailer->compose(
                        'student/updateTaskDeadline',
                        [
                            'task' => $task,
                            'actor' => Yii::$app->user->identity,
                            'group' => $group,
                        ]
                    )
                        ->setFrom(Yii::$app->params['systemEmail'])
                        ->setTo($subscription->user->notificationEmail)
                        ->setSubject(Yii::t('app/mail', 'Task deadline change'));
                }

                // Change the hard deadline in the repository git hook as well for version controlled tasks
                if (Yii::$app->params['versionControl']['enabled'] && $task->isVersionControlled) {
                    GitManager::afterTaskUpdate($task, $subscription);
                }
            }
            Yii::$app->language = $originalLanguage;

            // Send mass email notifications
            Yii::$app->mailer->sendMultiple($messages);
        }

        Yii::info(
            "A task has been updated: $task->name (id: $task->id)." . PHP_EOL .
            "Course and group: {$task->group->course->name} ({$task->group->number})",
            __METHOD__
        );

        return $task;
    }

    /**
     * Remove a task
     * @throws BadRequestHttpException
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     * @throws ServerErrorHttpException
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     *
     * @OA\Delete(
     *     path="/instructor/tasks/{id}",
     *     operationId="instructor::TasksController::actionDelete",
     *     tags={"Instructor Tasks"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=204,
     *         description="task deleted",
     *     ),
     *    @OA\Response(response=400, ref="#/components/responses/400"),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=403, ref="#/components/responses/403"),
     *    @OA\Response(response=404, ref="#/components/responses/404"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * )
     */
    public function actionDelete(int $id): void
    {
        // Fetch the entities.
        $task = TaskResource::findOne($id);

        if (is_null($task)) {
            throw new NotFoundHttpException(Yii::t('app', 'Task not found.'));
        }

        // Authorization check
        if (!Yii::$app->user->can('manageGroup', ['groupID' => $task->groupID])) {
            throw new ForbiddenHttpException(
                Yii::t('app', 'You must be an instructor of the group to perform this action!')
            );
        }

        // Canvas synchronization check
        if ($task->group->isCanvasCourse) {
            throw new BadRequestHttpException(
                Yii::t('app', 'This operation cannot be performed on a canvas synchronized course!')
            );
        }

        // Check semester
        if ($task->semesterID !== SemesterResource::getActualID()) {
            throw new BadRequestHttpException(
                Yii::t('app', "You can't modify a task from a previous semester!")
            );
        }

        // Count all submissions in task
        $allStudentFilesCount = StudentFile::find()
            ->andWhere(['taskID' => $task->id])
            ->count();

        // Query all 'No Submission' submissions
        $noSubmissionStudentFilesQuery = StudentFile::find()
            ->andWhere(['taskID' => $task->id])
            ->andWhere(['isAccepted' => StudentFile::IS_ACCEPTED_NO_SUBMISSION]);

        // Check if they match
        if ($allStudentFilesCount == $noSubmissionStudentFilesQuery->count()) {
            // Try to delete them.
            try {
                // Queries
                $instructorFiles = InstructorFile::findAll(['taskID' => $task->id]);
                $testCases = TestCase::findAll(['taskID' => $task->id]);
                $studentFiles = $noSubmissionStudentFilesQuery->all();
                // Delete instructor files.
                foreach ($instructorFiles as $file) {
                    // Delete the entry and the file from the disk.
                    $file->delete();
                }
                // Delete test cases
                foreach ($testCases as $case) {
                    // Delete the entity.
                    $case->delete();
                }
                // Delete (no submission) student files
                foreach ($studentFiles as $file) {
                    $file->delete();
                }

                if ($task->delete()) {
                    $this->response->statusCode = 204;
                    return;
                } else {
                    throw new ServerErrorHttpException(
                        Yii::t('app', 'Failed to delete task. Message: ') . Yii::t('app', "Database errors")
                    );
                }
            } catch (\yii\db\IntegrityException $e) {
                throw new BadRequestHttpException(
                    Yii::t(
                        'app',
                        'Failed to remove task. This task can not be removed anymore, there is uploaded solution!'
                    )
                );
            } catch (ErrorException $e) {
                throw new ServerErrorHttpException(
                    Yii::t('app', 'Failed to delete task. Message: ') . $e->getMessage()
                );
            }
        } else {
            throw new BadRequestHttpException(
                Yii::t(
                    'app',
                    'Failed to remove task. This task can not be removed anymore, there is uploaded solution!'
                )
            );
        }
    }

    /**
     * Filter tasks by courseID and semester.
     * This action is mainly used in plagiarism check form.
     * @param int|string $courseID
     * @param bool $myTasks
     * @param int $semesterFromID
     * @param int $semesterToID
     *
     * @OA\Get (
     *     path="/instructor/tasks/list-for-course",
     *     operationId="instructor::TasksController::actionListForCourse",
     *     tags={"Instructor Tasks"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *        name="courseID",
     *        in="query",
     *        required=true,
     *        description="ID of the course",
     *        @OA\Schema(ref="#/components/schemas/int_id")
     *     ),
     *     @OA\Parameter(
     *        name="myTasks",
     *        in="query",
     *        required=true,
     *        description="Show tasks only for the current user",
     *        @OA\Schema(type="boolean")
     *     ),
     *     @OA\Parameter(
     *        name="semesterFromID",
     *        in="query",
     *        required=true,
     *        description="ID of the first semester",
     *        @OA\Schema(ref="#/components/schemas/int_id")
     *     ),
     *     @OA\Parameter(
     *        name="semesterToID",
     *        in="query",
     *        required=true,
     *        description="ID of the last semester",
     *        @OA\Schema(ref="#/components/schemas/int_id")
     *     ),
     *     @OA\Parameter(ref="#/components/parameters/yii2_fields"),
     *     @OA\Parameter(ref="#/components/parameters/yii2_expand"),
     *     @OA\Parameter(ref="#/components/parameters/yii2_sort"),
     *     @OA\Response(
     *         response=200,
     *         description="tasks listed",
     *         @OA\JsonContent(ref="#/components/schemas/Instructor_TaskResource_Read"),
     *     ),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * )
     */
    public function actionListForCourse($courseID, bool $myTasks, int $semesterFromID, int $semesterToID): ActiveDataProvider
    {
        $groupQuery = Group::find();
        if ($courseID != 'All') {
            $groupQuery = $groupQuery->where(['courseID' => $courseID]);
        }
        if ($myTasks) {
            $groupQuery = $groupQuery->joinWith('instructorGroups')->andWhere(
                ['userID' => Yii::$app->user->id]
            );
        }

        $instIds = array_map(
            function ($o) {
                return $o->id;
            },
            $groupQuery->all()
        );

        $taskQuery = TaskResource::find()
            ->andWhere(['groupID' => $instIds])
            ->semesterInterval($semesterFromID, $semesterToID);

        return new ActiveDataProvider(
            [
                'query' => $taskQuery,
                'pagination' => false
            ]
        );
    }

    /**
     * List students for the given task ids
     * @throws NotFoundHttpException
     *
     * @OA\Post(
     *     path="/instructor/tasks/list-users",
     *     operationId="instructor::TasksController::actionListUsers",
     *     tags={"Instructor Tasks"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(ref="#/components/parameters/yii2_fields"),
     *     @OA\Parameter(ref="#/components/parameters/yii2_expand"),
     *     @OA\Parameter(ref="#/components/parameters/yii2_sort"),
     *     @OA\RequestBody(
     *         description="list of userIDs",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                  @OA\Property(property="ids", type="array", @OA\Items(ref="#/components/schemas/int_id")),
     *             ),
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="users",
     *         @OA\JsonContent(ref="#/components/schemas/Common_UserResource_Read"),
     *     ),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=404, ref="#/components/responses/404"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * )
     */
    public function actionListUsers(): ArrayDataProvider
    {
        $values = Yii::$app->request->post('ids', []);
        $studentsMap = [];

        // Process each task by their id.
        foreach ($values as $id) {
            $task = TaskResource::findOne($id);
            if (is_null($task)) {
                throw new NotFoundHttpException(
                    Yii::t('app', 'Task not found.') . " (taskID: $id)"
                );
            }

            // Combine all the found user, filter unique users
            foreach (Subscription::findAll(['groupID' => $task->groupID]) as $subscription) {
                $user = $subscription->user;
                $studentsMap[$user->id] = new UserResource($user);
            }
        }

        // Convert map to array
        $studentsList = [];
        foreach ($studentsMap as $student) {
            $studentsList[] = $student;
        }

        return new ArrayDataProvider(
            [
                'modelClass' => UserResource::class,
                'allModels' => $studentsList,
                'pagination' => false,
            ]
        );
    }

    /**
     * Updates CodeCompass parser properties for a task
     *
     * @param int $id the id of the task
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     * @throws ServerErrorHttpException
     * @throws ConflictHttpException
     *
     * @OA\Post(
     *     path="/instructor/tasks/{id}/setup-code-compass-parser",
     *     operationId="instructor::TasksController::actionSetupCodeCompassParser",
     *     tags={"Instructor Tasks"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *          name="id",
     *          in="path",
     *          required=true,
     *          description="ID of the task",
     *          @OA\Schema(ref="#/components/schemas/int_id")
     *     ),
     *     @OA\Parameter(ref="#/components/parameters/yii2_fields"),
     *     @OA\Parameter(ref="#/components/parameters/yii2_expand"),
     *     @OA\RequestBody(
     *         description="Code compass parser properties",
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(ref="#/components/schemas/Instructor_SetupCodeCompassParserResource_ScenarioDefault"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="updated Code compass parser properties",
     *         @OA\JsonContent(ref="#/components/schemas/Instructor_TaskResource_Read"),
     *     ),
     *    @OA\Response(response=403, ref="#/components/responses/403"),
     *    @OA\Response(response=404, ref="#/components/responses/404"),
     *    @OA\Response(response=409, ref="#/components/responses/409"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * )
     */
    public function actionSetupCodeCompassParser(int $id): TaskResource
    {
        if (!CodeCompassHelper::isCodeCompassIntegrationEnabled()) {
            throw new ForbiddenHttpException(
                Yii::t('app', 'CodeCompass is not enabled.')
            );
        }

        $task = TaskResource::findOne($id);
        if (is_null($task)) {
            throw new NotFoundHttpException(Yii::t('app', 'Task not found.'));
        }

        if (!Yii::$app->user->can('manageGroup', ['groupID' => $task->groupID])) {
            throw new ForbiddenHttpException(
                Yii::t('app', 'You must be an instructor of the group to perform this action!')
            );
        }

        $setupData = new SetupCodeCompassParserResource();
        $setupData->load(Yii::$app->request->post(), '');

        $packagesChanged = $setupData->codeCompassPackagesInstallInstructions != $task->codeCompassPackagesInstallInstructions;

        $task->codeCompassCompileInstructions = $setupData->codeCompassCompileInstructions;
        $task->codeCompassPackagesInstallInstructions = $setupData->codeCompassPackagesInstallInstructions;

        if ($packagesChanged) {
            try {
                CodeCompassHelper::deleteCachedImageForTask($id, CodeCompassHelper::createDockerClient());
            } catch (ImageDeleteConflictException $ex) {
                throw new ConflictHttpException(Yii::t(
                    'app',
                    'Cannot change package installing script while CodeCompass is running!')
                );
            }
        }

        if ($task->save(false)) {
            return $task;
        } else {
            throw new ServerErrorHttpException(Yii::t('app', 'A database error occurred'));
        }
    }
}
