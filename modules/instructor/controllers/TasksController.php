<?php

namespace app\modules\instructor\controllers;

use app\components\AssignmentTester;
use app\components\CodeCompassHelper;
use app\components\GitManager;
use app\models\Group;
use app\models\InstructorFile;
use app\models\StudentFile;
use app\models\Subscription;
use app\models\Task;
use app\models\TestCase;
use app\modules\instructor\resources\GroupResource;
use app\modules\instructor\resources\SetupAutoTesterResource;
use app\modules\instructor\resources\SetupCodeCompassParserResource;
use app\modules\instructor\resources\TaskResource;
use app\modules\instructor\resources\TesterFormDataResource;
use app\resources\SemesterResource;
use app\resources\UserResource;
use Docker\API\Exception\ImageDeleteConflictException;
use Yii;
use yii\base\ErrorException;
use yii\data\ActiveDataProvider;
use yii\data\ArrayDataProvider;
use yii\helpers\FileHelper;
use yii\web\BadRequestHttpException;
use yii\web\ConflictHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;
use yii\web\UploadedFile;


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
                'toggle-auto-tester' => ['PATCH'],
                'setup-auto-tester' => ['POST'],
                'tester-form-data' => ['GET'],
                'update-docker-image' => ['PATCH'],
                'setup-code-compass-parser' => ['POST']
            ]
        );
    }

    /**
     * List tasks for the given group
     * @param int $groupID
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
    public function actionIndex($groupID)
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
     * @param $id
     * @return TaskResource
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
    public function actionView($id)
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

        // Create remote repository for everybody in the group if the task is version controlled
        if (Yii::$app->params['versionControl']['enabled'] && $task->isVersionControlled) {
            foreach ($task->group->subscriptions as $subcription) {
                GitManager::createRepositories($task, $subcription->user);
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
     * @param int $id
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
    public function actionUpdate($id)
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
     * @param int $id
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
    public function actionDelete($id)
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

        $instructorFiles = InstructorFile::findAll(['taskID' => $task->id]);
        $studentFiles = StudentFile::findAll(['taskID' => $task->id]);
        $testCases = TestCase::findAll(['taskID' => $task->id]);

        // Check for student solutions
        if (count($studentFiles) == 0) {
            // Try to delete them.
            try {
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

                if ($task->delete()) {
                    $this->response->statusCode = 204;
                    return;
                } else {
                    throw new ServerErrorHttpException(
                        Yii::t('app', 'Failed to delete task. Message: ') . Yii::t('app', "Database errors")
                    );
                }
            } catch (yii\db\IntegrityException $e) {
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
     * @param mixed $myTasks
     * @param int $semesterFromID
     * @param int $semesterToID
     * @return ActiveDataProvider
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
    public function actionListForCourse($courseID, $myTasks, $semesterFromID, $semesterToID)
    {
        $myTasks = filter_var($myTasks, FILTER_VALIDATE_BOOLEAN);
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
     * @return ArrayDataProvider
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
    public function actionListUsers()
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
     * Turn auto tester on or off for the given task
     * @param $id
     * @return TaskResource
     * @throws BadRequestHttpException
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     * @throws ServerErrorHttpException
     *
     * @OA\Patch(
     *     path="/instructor/tasks/{id}/toggle-auto-tester",
     *     operationId="instructor::TasksController::actionToggleAutoTester",
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
     *     @OA\Response(
     *         response=200,
     *         description="task updated",
     *         @OA\JsonContent(ref="#/components/schemas/Instructor_TaskResource_Read"),
     *     ),
     *    @OA\Response(response=400, ref="#/components/responses/400"),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=403, ref="#/components/responses/403"),
     *    @OA\Response(response=404, ref="#/components/responses/404"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * )
     */
    public function actionToggleAutoTester($id)
    {
        if (!Yii::$app->params['evaluator']['enabled']) {
            throw new BadRequestHttpException(
                Yii::t('app', 'Auto tester is disabled. Contact the administrator for more information.')
            );
        }

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

        // Check semester
        if ($task->semesterID !== SemesterResource::getActualID()) {
            throw new BadRequestHttpException(
                Yii::t('app', "You can't modify a task from a previous semester!")
            );
        }

        $task->autoTest = $task->autoTest === 0 ? 1 : 0;
        if (!$task->save()) {
            throw new ServerErrorHttpException(
                Yii::t('app', 'Failed to save task. Message: ') . Yii::t('app', 'A database error occurred')
            );
        }

        return $task;
    }

    /**
     * Updates auto tester for a task
     *
     * @param int $id the id of the task
     * @return TaskResource|array
     * @throws BadRequestHttpException
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     * @throws ServerErrorHttpException
     * @throws ErrorException
     *
     * @OA\Post(
     *     path="/instructor/tasks/{id}/setup-auto-tester",
     *     operationId="instructor::TasksController::actionSetupAutoTester",
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
     *         description="tester data task",
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(ref="#/components/schemas/Instructor_SetupAutoTesterResource_ScenarioDefault"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="updated task",
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
    public function actionSetupAutoTester($id)
    {
        if (!Yii::$app->params['evaluator']['enabled']) {
            throw new BadRequestHttpException(
                Yii::t('app', 'Auto tester is disabled. Contact the administrator for more information.')
            );
        }

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

        // Check semester
        if ($task->semesterID !== SemesterResource::getActualID()) {
            throw new BadRequestHttpException(
                Yii::t('app', "You can't modify a task from a previous semester!")
            );
        }

        $setupData = new SetupAutoTesterResource();
        $setupData->load(Yii::$app->request->post(), '');
        $setupData->files = UploadedFile::getInstancesByName('files');

        $task->testOS = $setupData->testOS;
        $task->imageName = $setupData->imageName;
        $task->compileInstructions = $setupData->compileInstructions;
        $task->runInstructions = $setupData->runInstructions;
        $task->showFullErrorMsg = $setupData->showFullErrorMsg;

        if (!$task->validate()) {
            $this->response->statusCode = 422;
            return $setupData->errors;
        }

        $sourcedir = Yii::$app->basePath . '/' . Yii::$app->params['data_dir'] .
            '/tmp/instructor/' . $task->groupID . '/' . $task->id . '/autotest/';

        // Create tmp dir for the given groupID and taskID
        if (file_exists($sourcedir)) {
            FileHelper::removeDirectory($sourcedir);
        }
        mkdir($sourcedir, 0755, true);

        if ($setupData->files) {
            foreach ($setupData->files as $file) {
                if (!$file->saveAs($sourcedir . $file->name)) {
                    // Log
                    Yii::error(
                        "Failed to save file to the disc ($file->name), error code: $file->error",
                        __METHOD__
                    );
                    throw new ServerErrorHttpException(
                        Yii::t("app", "Failed to save file. Error logged.") . " ($file->name)"
                    );
                }
            }
        }

        if (file_exists($sourcedir . 'Dockerfile')) {
           if (AssignmentTester::alreadyBuilt($task->localImageName, Yii::$app->params['evaluator'][$task->testOS])) {
                AssignmentTester::removeImage($task->localImageName, Yii::$app->params['evaluator'][$task->testOS]);
            }

            $buildResult = AssignmentTester::buildImageForTask(
                $task->localImageName,
                $sourcedir,
                Yii::$app->params['evaluator'][$task->testOS]
            );

            if (!$buildResult['success']) {
                $error = $buildResult['log'] . PHP_EOL . $buildResult['error'];
                throw new ServerErrorHttpException($error);
            } else {
                $task->imageName = $task->localImageName;
            }
        }

        if (
            !$task->isLocalImage &&
            !AssignmentTester::alreadyBuilt($task->imageName, Yii::$app->params['evaluator'][$task->testOS])
        ) {
            AssignmentTester::pullImage($task->imageName, Yii::$app->params['evaluator'][$task->testOS]);
        }

        // Clean temp files
        if (file_exists($sourcedir)) {
            FileHelper::removeDirectory($sourcedir);
        }

        if ($task->save(false)) {
            if($setupData->reevaluateAutoTest){
                StudentFile::updateAll(
                    ['isAccepted' => StudentFile::IS_ACCEPTED_UPLOADED],
                    [
                        'and',
                        ['in', 'isAccepted', [StudentFile::IS_ACCEPTED_PASSED, StudentFile::IS_ACCEPTED_FAILED]],
                        ['=', 'taskID', $task->id],
                    ]
                );
            }
            return $task;
        } else {
            throw new ServerErrorHttpException(Yii::t('app', 'A database error occurred'));
        }
    }

    /**
     * Provides data for auto tester setup
     * @param int $id
     * @return TesterFormDataResource
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     * @throws BadRequestHttpException
     *
     * @OA\Get(
     *     path="/instructor/tasks/{id}/tester-form-data",
     *     operationId="instructor::TasksController::actionTesterFormData",
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
     *     @OA\Response(
     *         response=200,
     *         description="updated task",
     *         @OA\JsonContent(ref="#/components/schemas/Instructor_TesterFormDataResource_Read"),
     *     ),
     *     @OA\Response(response=400, ref="#/components/responses/400"),
     *     @OA\Response(response=401, ref="#/components/responses/401"),
     *     @OA\Response(response=403, ref="#/components/responses/403"),
     *     @OA\Response(response=404, ref="#/components/responses/404"),
     *     @OA\Response(response=500, ref="#/components/responses/500"),
     * )
     */
    public function actionTesterFormData($id)
    {
        if (!Yii::$app->params['evaluator']['enabled']) {
            throw new BadRequestHttpException(
                Yii::t('app', 'Auto tester is disabled. Contact the administrator for more information.')
            );
        }

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

        $templates = [];
        $osMap = $task->testOSMap();

        foreach (Yii::$app->params['evaluator']['templates'] as $key => $template) {
            if (in_array($template['os'], array_keys($osMap))) {
                $templates[] = $template;
            }
        }

        $response = new TesterFormDataResource();
        $response->templates = $templates;
        $response->osMap = $osMap;
        $response->imageSuccessfullyBuilt = AssignmentTester::alreadyBuilt(
            $task->imageName,
            Yii::$app->params['evaluator'][$task->testOS]
        );
        if ($response->imageSuccessfullyBuilt) {
            $response->imageCreationDate = AssignmentTester::inspectImage(
                $task->imageName,
                Yii::$app->params['evaluator'][$task->testOS]
            )->getCreated();
        }
        return $response;
    }

    /**
     * @param int $id
     * @return TaskResource
     * @throws BadRequestHttpException
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     *
     * @OA\Patch (
     *     path="/instructor/tasks/{id}/update-docker-image",
     *     operationId="instructor::TasksController::udapteDockerImage",
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
     *     @OA\Response(
     *         response=200,
     *         description="updated task",
     *         @OA\JsonContent(ref="#/components/schemas/Instructor_TesterFormDataResource_Read"),
     *     ),
     *     @OA\Response(response=400, ref="#/components/responses/400"),
     *     @OA\Response(response=401, ref="#/components/responses/401"),
     *     @OA\Response(response=403, ref="#/components/responses/403"),
     *     @OA\Response(response=404, ref="#/components/responses/404"),
     *     @OA\Response(response=500, ref="#/components/responses/500"),
     * )
     */
    public function actionUpdateDockerImage($id)
    {
        if (!Yii::$app->params['evaluator']['enabled']) {
            throw new BadRequestHttpException(
                Yii::t('app', 'Auto tester is disabled. Contact the administrator for more information.')
            );
        }

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

        if (!$task->isLocalImage) {
            AssignmentTester::pullImage($task->imageName, Yii::$app->params['evaluator'][$task->testOS]);
        } else {
            throw new BadRequestHttpException(Yii::t('app', 'Local Docker images can\'t be updated from registry.'));
        }

        return $task;
    }

    /**
     * Updates CodeCompass parser properties for a task
     *
     * @param int $id the id of the task
     * @return TaskResource
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
