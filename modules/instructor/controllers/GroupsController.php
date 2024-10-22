<?php

namespace app\modules\instructor\controllers;

use app\components\GitManager;
use app\models\Group;
use app\models\InstructorFile;
use app\models\InstructorGroup;
use app\models\Semester;
use app\models\StudentFile;
use app\models\Subscription;
use app\models\Task;
use app\modules\instructor\resources\GroupResource;
use app\modules\instructor\resources\GroupSubmittedStatsResource;
use app\modules\instructor\resources\GroupTaskStatsResource;
use app\modules\instructor\resources\NotesResource;
use app\modules\instructor\resources\StudentStatsResource;
use app\resources\AddUsersListResource;
use app\resources\SemesterResource;
use app\resources\UserAddErrorResource;
use app\resources\UserResource;
use app\resources\UsersAddedResource;
use Exception;
use Throwable;
use Yii;
use yii\base\ErrorException;
use yii\data\ActiveDataProvider;
use yii\db\StaleObjectException;
use yii\helpers\FileHelper;
use yii\helpers\VarDumper;
use yii\web\BadRequestHttpException;
use yii\web\ConflictHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;
use app\exceptions\AddFailedException;
use yii\mail\MessageInterface;

/**
* @OA\PathItem(
 *   path="/instructor/groups/{id}",
 *   @OA\Parameter(
 *      name="id",
 *      in="path",
 *      required=true,
 *      description="ID of the group",
 *      @OA\Schema(ref="#/components/schemas/int_id"),
 *   ),
 * ),
 * @OA\PathItem(
 *   path="/instructor/groups/{groupID}/students",
 *   @OA\Parameter(
 *      name="groupID",
 *      in="path",
 *      required=true,
 *      description="ID of the group",
 *      @OA\Schema(ref="#/components/schemas/int_id"),
 *   ),
 * ),
 * @OA\PathItem(
 *   path="/instructor/groups/{groupID}/instructors",
 *   @OA\Parameter(
 *      name="groupID",
 *      in="path",
 *      required=true,
 *      description="ID of the group",
 *      @OA\Schema(ref="#/components/schemas/int_id"),
 *   ),
 * ),
*/

/**
 * This class provides access to groups for instructors
 */
class GroupsController extends BaseInstructorRestController
{
    /**
     * @inheritdoc
     */
    protected function verbs(): array
    {
        return array_merge(parent::verbs(), [
            'index' => ['GET'],
            'view' => ['GET'],
            'create' => ['POST'],
            'delete' => ['DELETE'],
            'update' => ['PATCH', 'PUT'],
            'duplicate' => ['POST'],
            'list-students' => ['GET'],
            'list-instructors' => ['GET'],
            'delete-instructor' => ['DELETE'],
            'delete-student' => ['DELETE'],
            'add-students' => ['POST'],
            'add-student-notes' => ['PUT'],
            'student-notes' => ['GET'],
            'add-instructors' => ['POST'],
            'group-stats' => ['GET'],
            'student-stats' => ['GET'],
        ]);
    }

    /**
     * List groups for a course and a semester
     *
     * @OA\Get(
     *     path="/instructor/groups",
     *     operationId="instructor::GroupsController::actionIndex",
     *     tags={"Instructor Groups"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(ref="#/components/parameters/yii2_fields"),
     *     @OA\Parameter(ref="#/components/parameters/yii2_expand"),
     *     @OA\Parameter(ref="#/components/parameters/yii2_sort"),
     *     @OA\Parameter(
     *         name="semesterID",
     *         in="query",
     *         required=true,
     *         description="ID of the semester",
     *         explode=true,
     *         @OA\Schema(ref="#/components/schemas/int_id")
     *     ),
     *     @OA\Parameter(
     *         name="courseID",
     *         in="query",
     *         required=false,
     *         description="ID of the course (optional)",
     *         explode=true,
     *         @OA\Schema(ref="#/components/schemas/int_id")
     *     ),
     *     @OA\Response(
     *        response=200,
     *        description="successful operation",
     *        @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Instructor_GroupResource_Read")),
     *    ),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * ),
     */
    public function actionIndex(int $semesterID, ?int $courseID = null): ActiveDataProvider
    {
        $userID = Yii::$app->user->id;

        $query = GroupResource::find()->instructorAccessibleGroups($userID, $semesterID, $courseID);

        return new ActiveDataProvider(
            [
                'query' => $query,
                'sort' => [
                    'defaultOrder' => [
                        'courseID' => SORT_ASC,
                        'number' => SORT_ASC,
                    ]
                ],
                'pagination' => false,
            ]
        );
    }

    /**
     * View group information
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     *
     * @OA\Get(
     *     path="/instructor/groups/{id}",
     *     operationId="instructor::GroupsController::actionView",
     *     tags={"Instructor Groups"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(ref="#/components/parameters/yii2_fields"),
     *     @OA\Parameter(ref="#/components/parameters/yii2_expand"),
     *     @OA\Response(
     *         response=200,
     *         description="successful operation",
     *         @OA\JsonContent(ref="#/components/schemas/Instructor_GroupResource_Read"),
     *     ),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=403, ref="#/components/responses/403"),
     *    @OA\Response(response=404, ref="#/components/responses/404"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * ),
     */
    public function actionView(int $id): GroupResource
    {
        $group = GroupResource::findOne($id);

        if (is_null($group)) {
            throw new NotFoundHttpException(Yii::t('app','Group not found.'));
        }

        // Authorization check
        if (!Yii::$app->user->can('manageGroup', ['groupID' => $id])) {
            throw new ForbiddenHttpException(
                Yii::t('app', 'You must be an instructor of the group to perform this action!')
            );
        }

        return $group;
    }

    /**
     * Add a group to a course
     * @return GroupResource|array
     * @throws ForbiddenHttpException
     * @throws ServerErrorHttpException
     *
     * @OA\Post(
     *     path="/instructor/groups",
     *     operationId="instructor::GroupsController::actionCreate",
     *     tags={"Instructor Groups"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(ref="#/components/parameters/yii2_fields"),
     *     @OA\Parameter(ref="#/components/parameters/yii2_expand"),
     *     @OA\RequestBody(
     *         description="new group",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(ref="#/components/schemas/Instructor_GroupResource_ScenarioCreate"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="new group created",
     *         @OA\JsonContent(ref="#/components/schemas/Instructor_GroupResource_Read"),
     *     ),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=403, ref="#/components/responses/403"),
     *    @OA\Response(response=422, ref="#/components/responses/422"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * ),
     */
    public function actionCreate()
    {
        $group = new GroupResource();
        $group->scenario = GroupResource::SCENARIO_CREATE;
        $group->load(Yii::$app->request->post(), '');
        $group->semesterID = SemesterResource::getActualID();

        if (!$group->validate()) {
            $this->response->statusCode = 422;
            return $group->errors;
        }

        // Authorization check
        if (!Yii::$app->user->can('manageCourse', ['courseID' => $group->courseID])) {
            throw new ForbiddenHttpException(
                Yii::t('app', 'You must be a lecturer of the course to perform this action!')
            );
        }

        if (!$group->save(false)) {
            throw new ServerErrorHttpException(
                Yii::t('app', 'Failed to add group. Message: ') .
                Yii::t('app', 'A database error occurred')
            );
        }

        $this->response->statusCode = 201;
        return $group;
    }

    /**
     * Delete a group
     * @throws BadRequestHttpException
     * @throws ConflictHttpException
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     * @throws ServerErrorHttpException
     * @throws Throwable
     * @throws StaleObjectException
     *
     * @OA\Delete(
     *     path="/instructor/groups/{id}",
     *     operationId="instructor::GroupsController::actionDelete",
     *     tags={"Instructor Groups"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=204,
     *         description="group deleted",
     *     ),
     *    @OA\Response(response=400, ref="#/components/responses/400"),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=403, ref="#/components/responses/403"),
     *    @OA\Response(response=404, ref="#/components/responses/404"),
     *    @OA\Response(response=409, ref="#/components/responses/409"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * )
     */
    public function actionDelete(int $id): void
    {
        $group = GroupResource::findOne($id);
        if (is_null($group)) {
            throw new NotFoundHttpException("Group not found");
        }

        // Authorization check
        if (!Yii::$app->user->can('manageCourse', ['groupID' => $id])) {
            throw new ForbiddenHttpException(
                Yii::t('app', 'You must be a lecturer of the course to perform this action!')
            );
        }

        // Canvas synchronization check
        if ($group->isCanvasCourse) {
            throw new BadRequestHttpException(
                Yii::t('app', 'This operation cannot be performed on a canvas synchronized course!')
            );
        }

        // Check semester
        if ($group->semesterID !== SemesterResource::getActualID()) {
            throw new BadRequestHttpException(
                Yii::t('app', "You can't modify a group from a previous semester!")
            );
        }

        // Try to delete the entry.
        try {
            if ($group->delete()) {
                // Returns empty page with status code 204
                $this->response->statusCode = 204;
                return;
            } else {
                throw new ServerErrorHttpException(
                    Yii::t('app', 'Failed to remove group. Message: ')
                    . Yii::t('app', 'A database error occurred'));
            }
        } catch (\yii\db\IntegrityException $e) {
            throw new ConflictHttpException(Yii::t('app', 'Failed to remove group. First you should remove the corresponding tasks!'));
        } catch (ErrorException $e) {
            throw new ServerErrorHttpException(
                Yii::t('app', 'Failed to remove group. Message: ')
                . Yii::t('app', 'A database error occurred'));
        }
    }

    /**
     * Update a group
     * @return GroupResource|array
     * @throws BadRequestHttpException
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     * @throws ServerErrorHttpException
     *
     * @OA\Put(
     *    path="/instructor/groups/{id}",
     *    operationId="instructor::GroupsController::actionUpdate",
     *    tags={"Instructor Groups"},
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(ref="#/components/parameters/yii2_fields"),
     *    @OA\Parameter(ref="#/components/parameters/yii2_expand"),
     *    @OA\RequestBody(
     *        description="updated group",
     *        @OA\MediaType(
     *            mediaType="application/json",
     *            @OA\Schema(ref="#/components/schemas/Instructor_GroupResource_ScenarioUpdate"),
     *        )
     *    ),
     *    @OA\Response(
     *        response=200,
     *        description="group updated",
     *        @OA\JsonContent(ref="#/components/schemas/Instructor_GroupResource_Read"),
     *    ),
     *    @OA\Response(response=400, ref="#/components/responses/400"),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=404, ref="#/components/responses/404"),
     *    @OA\Response(response=422, ref="#/components/responses/422"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * ),
     */
    public function actionUpdate(int $id)
    {
        $group = GroupResource::findOne($id);
        if (is_null($group)) {
            throw new NotFoundHttpException("Group not found");
        }

        // Authorization check
        if (!Yii::$app->user->can('manageCourse', ['groupID' => $id])) {
            throw new ForbiddenHttpException(
                Yii::t('app', 'You must be a lecturer of the course to perform this action!')
            );
        }

        // Check semester
        if ($group->semesterID !== SemesterResource::getActualID()) {
            throw new BadRequestHttpException(
                Yii::t('app', "You can't modify a group from a previous semester!")
            );
        }

        $group->scenario = GroupResource::SCENARIO_UPDATE;
        $group->load(Yii::$app->request->post(), '');

        if ($group->save()) {
            return $group;
        } elseif ($group->hasErrors()) {
            $this->response->statusCode = 422;
            return $group->errors;
        } else {
            throw new ServerErrorHttpException(
                Yii::t('app', 'Failed to update group. Message: ')
                . Yii::t('app', 'A database error occurred'));
        }
    }

    /**
     * Duplicate a group
     * @throws BadRequestHttpException
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     * @throws ServerErrorHttpException
     * @throws ErrorException
     * @throws \yii\db\Exception
     *
     * @OA\Post(
     *     path="/instructor/groups/{id}/duplicate",
     *     tags={"Instructor Groups"},
     *     operationId="instructor::GroupsController::actionDuplicate",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *        name="id",
     *        in="path",
     *        required=true,
     *        description="ID of the group",
     *        @OA\Schema(ref="#/components/schemas/int_id")
     *     ),
     *     @OA\Parameter(ref="#/components/parameters/yii2_fields"),
     *     @OA\Parameter(ref="#/components/parameters/yii2_expand"),
     *     @OA\Response(
     *         response=200,
     *         description="group duplicated",
     *         @OA\JsonContent(ref="#/components/schemas/Instructor_GroupResource_Read"),
     *     ),
     *    @OA\Response(response=400, ref="#/components/responses/400"),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=403, ref="#/components/responses/403"),
     *    @OA\Response(response=422, ref="#/components/responses/422"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * )
     */
    public function actionDuplicate(int $id): GroupResource
    {
        $groupToDuplicate = Group::findOne($id);
        if (is_null($groupToDuplicate)) {
            throw new NotFoundHttpException('Group not found');
        }

        // Authorization check
        if (!Yii::$app->user->can('manageCourse', ['groupID' => $id])) {
            throw new ForbiddenHttpException(
                Yii::t('app', 'You must be a lecturer of the course to perform this action!')
            );
        }

        // Canvas synchronization check
        if ($groupToDuplicate->isCanvasCourse) {
            throw new BadRequestHttpException(
                Yii::t('app', 'This operation cannot be performed on a canvas synchronized course!')
            );
        }

        // Create the new entry based on the original one.
        $actualSemester = Semester::getActualID();
        $group = new Group($groupToDuplicate);
        unset($group->id);
        $group->semesterID = $actualSemester;
        $group->number = null;

        $transaction = Yii::$app->db->beginTransaction();
        $directoryPaths = [];
        try {
            // If it can be saved we copy the tasks and the Instructor-Group ID pairs from the connection table.
            if ($group->save()) {
                $idPairsToCopy = InstructorGroup::findAll(['groupID' => $groupToDuplicate->id]);
                foreach ($idPairsToCopy as $idPairToCopy) {
                    $newRecord = new InstructorGroup();
                    $newRecord->userID = $idPairToCopy->userID;
                    $newRecord->groupID = $group->id;
                    if (!$newRecord->save()) {
                        throw new ServerErrorHttpException(
                            'Failed to save InstructorGroup to the database: ' . VarDumper::dumpAsString($newRecord->firstErrors)
                        );
                    }
                }

                $tasksToDuplicate = Task::findAll(['groupID' => $groupToDuplicate->id]);
                foreach ($tasksToDuplicate as $taskToDuplicate) {
                    $task = new Task($taskToDuplicate);
                    unset($task->id);
                    $task->groupID = $group->id;
                    $task->semesterID = $actualSemester;

                    if ($task->save()) {
                        // If the task can be saved we copy the files as well.
                        $filesToDuplicate = InstructorFile::findAll(['taskID' => $taskToDuplicate->id]);
                        $directoryPath = Yii::getAlias("@appdata/uploadedfiles/") . $task->id . '/';
                        $directoryPaths[] = $directoryPath;
                        foreach ($filesToDuplicate as $fileToDuplicate) {
                            $file = new InstructorFile($fileToDuplicate);
                            unset($file->id);
                            $file->taskID = $task->id;

                            $filePath = $directoryPath . $fileToDuplicate->name;
                            copy($fileToDuplicate->path, $filePath);
                            $filePaths[] = $filePath;

                            if (!$file->save()) {
                                throw new ServerErrorHttpException(
                                    'Failed to save InstructorFile to the database: ' . VarDumper::dumpAsString($file->firstErrors)
                                );
                            }
                        }
                    } else {
                        throw new ServerErrorHttpException('Failed to save Task to the database: ' . VarDumper::dumpAsString($task->firstErrors));
                    }
                }

                $transaction->commit();
                $this->response->statusCode = 201;
                return new GroupResource($group);
            } else {
                throw new ServerErrorHttpException('Failed to save group:' . VarDumper::dumpAsString($group->firstErrors));
            }
        } catch (Exception $e) {
            $transaction->rollBack();

            foreach ($directoryPaths as $dir) {
                FileHelper::removeDirectory($dir);
            }

            throw $e;
        }
    }

    /**
     * List instructors for the given group
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     *
     * @OA\Get(
     *     path="/instructor/groups/{groupID}/instructors",
     *     operationId="instructor::GroupsController::actionListInstructors",
     *     tags={"Instructor Groups"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(ref="#/components/parameters/yii2_fields"),
     *     @OA\Parameter(ref="#/components/parameters/yii2_expand"),
     *     @OA\Parameter(ref="#/components/parameters/yii2_sort"),
     *     @OA\Response(
     *         response=200,
     *         description="successful operation",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Common_UserResource_Read")),
     *     ),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=403, ref="#/components/responses/403"),
     *    @OA\Response(response=404, ref="#/components/responses/404"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * ),
     */
    public function actionListInstructors(int $groupID): ActiveDataProvider
    {
        $group = GroupResource::findOne($groupID);

        if (is_null($group)) {
            throw new NotFoundHttpException(Yii::t('app', 'Group not found.'));
        }

        // Authorization check
        if (!Yii::$app->user->can('manageGroup', ['groupID' => $groupID])) {
            throw new ForbiddenHttpException(
                Yii::t('app', 'You must be an instructor of the group to perform this action!')
            );
        }

        return new ActiveDataProvider(
            [
                'query' => $group->getInstructors(),
                'pagination' => false
            ]
        );
    }

    /**
     * Add instructors to a group
     * @return array|UsersAddedResource
     * @throws BadRequestHttpException
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     *
     * @OA\Post(
     *     path="/instructor/groups/{groupID}/instructors",
     *     operationId="instructor::GroupsController::actionAddInstructors",
     *     tags={"Instructor Groups"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         description="list of instructors",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(ref="#/components/schemas/Common_AddUsersListResource_ScenarioDefault"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=207,
     *         description="multistatus result",
     *         @OA\JsonContent(ref="#/components/schemas/Common_UsersAddedResource_Read"),
     *     ),
     *    @OA\Response(response=400, ref="#/components/responses/400"),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=403, ref="#/components/responses/403"),
     *    @OA\Response(response=404, ref="#/components/responses/404"),
     *    @OA\Response(response=422, ref="#/components/responses/422"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * ),
     */
    public function actionAddInstructors(int $groupID)
    {
        $group = GroupResource::findOne($groupID);

        if (is_null($group)) {
            throw new NotFoundHttpException(Yii::t('app', 'Group not found.'));
        }

        // Authorization check
        if (!Yii::$app->user->can('manageCourse', ['groupID' => $groupID])) {
            throw new ForbiddenHttpException(
                Yii::t('app', 'You must be a lecturer of the course to perform this action!')
            );
        }

        // Check semester
        if ($group->semesterID !== SemesterResource::getActualID()) {
            throw new BadRequestHttpException(
                Yii::t('app', "You can't modify a group from a previous semester!")
            );
        }

        $model = new AddUsersListResource();
        $model->load(Yii::$app->request->post(), '');
        if ($model->validate()) {
            $this->response->statusCode = 207;
            return $this->processInstructors($model->userCodes, $groupID);
        } else {
            $this->response->statusCode = 422;
            return $model->errors;
        }
    }

    /**
     * Process the received list and saves them one by one.
     * @param string[] $userCodes
     * @param int $groupID
     * @throws Exception
     */
    private function processInstructors(array $userCodes, int $groupID): UsersAddedResource
    {
        /** @var MessageInterface[] Email notifications */
        $messages = [];
        /** @var UserResource[] */
        $users = [];
        /** @var UserAddErrorResource[] */
        $failed = [];

        foreach ($userCodes as $userCode) {
            try {
                $user = UserResource::findOne(['userCode' => $userCode]);

                if (is_null($user)) {
                    throw new AddFailedException($userCode, ['userCode' => [ Yii::t('app', 'User not found found.')]]);
                }


                // Add the instructor to the group.
                $instructorGroup = new InstructorGroup(
                    [
                        'userID' => $user->id,
                        'groupID' => $groupID,
                    ]
                );

                if (!$instructorGroup->save()) {
                    throw new AddFailedException($userCode, $instructorGroup->errors);
                }

                // Assign faculty role if necessary
                $authManager = Yii::$app->authManager;
                if (!$authManager->checkAccess($user->id, 'faculty')) {
                    $authManager->assign($authManager->getRole('faculty'), $user->id);
                }

                $users[] = $user;
                if (!empty($user->notificationEmail)) {
                    $originalLanguage = Yii::$app->language;

                    Yii::$app->language = $user->locale;
                    $messages[] = Yii::$app->mailer->compose('instructor/newGroup', [
                        'group' => Group::findOne(['id' => $groupID]),
                        'actor' => Yii::$app->user->identity,
                    ])
                        ->setFrom(Yii::$app->params['systemEmail'])
                        ->setTo($user->notificationEmail)
                        ->setSubject(Yii::t('app/mail', 'New group assignment'));
                    Yii::$app->language = $originalLanguage;
                }
            } catch (AddFailedException $e) {
                $failed[] = new UserAddErrorResource($e->getIdentifier(), $e->getCause());
            }
        }
        // Send mass email notifications
        Yii::$app->mailer->sendMultiple($messages);

        $result = new UsersAddedResource();
        $result->addedUsers = $users;
        $result->failed = $failed;
        return $result;
    }

    /**
     * Remove an instructor from a group
     * @throws BadRequestHttpException
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     * @throws ServerErrorHttpException
     * @throws Throwable
     * @throws StaleObjectException
     *
     * @OA\Delete(
     *     path="/instructor/groups/{groupID}/instructors/{userID}",
     *     operationId="instructor::GroupsController::actionDeleteInstructor",
     *     tags={"Instructor Groups"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *           name="groupID",
     *           in="path",
     *           required=true,
     *           description="ID of the group",
     *           @OA\Schema(ref="#/components/schemas/int_id"),
     *     ),
     *     @OA\Parameter(
     *          name="userID",
     *          in="path",
     *          required=true,
     *          description="ID of the instructor",
     *          @OA\Schema(ref="#/components/schemas/int_id"),
     *    ),
     *    @OA\Response(
     *         response=204,
     *         description="instructor deleted from the group",
     *     ),
     *    @OA\Response(response=400, ref="#/components/responses/400"),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=403, ref="#/components/responses/403"),
     *    @OA\Response(response=404, ref="#/components/responses/404"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * ),
     */
    public function actionDeleteInstructor(int $groupID, int $userID): void
    {
        $instructorGroup = InstructorGroup::findOne(
            [
                'groupID' => $groupID,
                'userID' => $userID
            ]
        );

        if (is_null($instructorGroup)) {
            throw new NotFoundHttpException('InstructorGroup not found');
        }

        // Authorization check
        if (!Yii::$app->user->can('manageCourse', ['groupID' => $groupID])) {
            throw new ForbiddenHttpException(Yii::t('app', 'You must be a lecturer of the course to perform this action!'));
        }

        // Check semester
        if ($instructorGroup->group->semesterID !== SemesterResource::getActualID()) {
            throw new BadRequestHttpException(
                Yii::t('app', "You can't modify a group from a previous semester!")
            );
        }

        // Remove the instructor
        if ($instructorGroup->delete()) {
            $this->response->statusCode = 204;
        } else {
            throw new ServerErrorHttpException(Yii::t('app', 'Can not remove instructor. Message: ') . Yii::t('app', 'A database error occurred'));
        }
    }

    /**
     * List students for the given group
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     *
     * @OA\Get(
     *     path="/instructor/groups/{groupID}/students",
     *     operationId="instructor::GroupsController::actionListStudents",
     *     tags={"Instructor Groups"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(ref="#/components/parameters/yii2_fields"),
     *     @OA\Parameter(ref="#/components/parameters/yii2_expand"),
     *     @OA\Parameter(ref="#/components/parameters/yii2_sort"),
     *     @OA\Response(
     *         response=200,
     *         description="successful operation",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Common_UserResource_Read")),
     *     ),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=403, ref="#/components/responses/403"),
     *    @OA\Response(response=404, ref="#/components/responses/404"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * ),
     */
    public function actionListStudents(int $groupID): ActiveDataProvider
    {
        $group = GroupResource::findOne($groupID);

        if (is_null($group)) {
            throw new NotFoundHttpException("Group not found");
        }

        // Authorization check
        if (!Yii::$app->user->can('manageGroup', ['groupID' => $groupID])) {
            throw new ForbiddenHttpException(
                Yii::t('app', 'You must be an instructor of the group to perform this action!')
            );
        }

        return new ActiveDataProvider(
            [
                'query' => $group->getStudents(),
                'pagination' => false
            ]
        );
    }

    /**
     * Add students to a group
     * @return array|UsersAddedResource
     * @throws BadRequestHttpException
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     *
     * @OA\Post(
     *     path="/instructor/groups/{groupID}/students",
     *     operationId="instructor::GroupsController::actionAddStudents",
     *     tags={"Instructor Groups"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         description="list of students",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(ref="#/components/schemas/Common_AddUsersListResource_ScenarioDefault"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=207,
     *         description="multistatus result",
     *         @OA\JsonContent(ref="#/components/schemas/Common_UsersAddedResource_Read"),
     *     ),
     *    @OA\Response(response=400, ref="#/components/responses/400"),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=403, ref="#/components/responses/403"),
     *    @OA\Response(response=422, ref="#/components/responses/422"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * ),
     */
    public function actionAddStudents(int $groupID)
    {
        $group = GroupResource::findOne($groupID);

        if (is_null($group)) {
            throw new NotFoundHttpException("Group not found");
        }

        // Authorization check
        if (!Yii::$app->user->can('manageGroup', ['groupID' => $groupID])) {
            throw new ForbiddenHttpException(Yii::t('app', 'You must be an instructor of the group to perform this action!'));
        }

        // Check semester
        if ($group->semesterID !== SemesterResource::getActualID()) {
            throw new BadRequestHttpException(
                Yii::t('app', "You can't modify a group from a previous semester!")
            );
        }

        // Canvas synchronization check
        if ($group->isCanvasCourse) {
            throw new BadRequestHttpException(
                Yii::t('app', 'This operation cannot be performed on a canvas synchronized course!')
            );
        }

        $model = new AddUsersListResource();
        $model->load(Yii::$app->request->post(), '');
        if ($model->validate()) {
            $this->response->statusCode = 207;
            return $this->processStudents($model->userCodes, $group);
        } else {
            $this->response->statusCode = 422;
            return $model->errors;
        }
    }

    /**
     * Process the received list and saves them one by one.
     * @param string[] $userCodes
     * @param GroupResource $group
     */
    private function processStudents(array $userCodes, GroupResource $group): UsersAddedResource
    {
        /** @var MessageInterface[] Email notifications */
        $messages = [];
        /** @var UserResource[] */
        $users = [];
        /** @var UserAddErrorResource[] */
        $failed = [];

        foreach ($userCodes as $userCode) {
            try {
                // First we try as an id aka already existing user.
                $user = UserResource::findOne(['userCode' => $userCode]);
                if (is_null($user)) {
                    $user = new UserResource();
                    $user->userCode = strtolower($userCode);
                    $user->locale = Yii::$app->language;

                    if (!$user->save()) {
                        throw new AddFailedException($userCode, $user->errors);
                    }
                }


                // Add the student to the group.
                $subscription = new Subscription(
                    [
                        'groupID' => $group->id,
                        'semesterID' => $group->semesterID,
                        'userID' => $user->id
                    ]
                );

                // Create new studentfile for student to add to tasks in group
                $tasks = Task::findAll(['groupID' => $group->id]);
                foreach ($tasks as $task) {
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
                        if ($task->isVersionControlled) {
                            GitManager::createUserRepository($task, $user);
                        }
                        Yii::info(
                            "A new blank solution has been uploaded for " .
                            "{$studentFile->task->name} ($studentFile->taskID)",
                            __METHOD__
                        );
                    } else {
                        throw new AddFailedException($userCode, [Yii::t('app', "A database error occurred")]);
                    }
                }

                if (!$subscription->save()) {
                    throw new AddFailedException($userCode, $subscription->errors);
                }

                if (!empty($user->notificationEmail)) {
                    $originalLanguage = Yii::$app->language;
                    Yii::$app->language = $user->locale;
                    $messages[] = Yii::$app->mailer->compose(
                        'student/newGroup',
                        [
                            'group' => Group::findOne(['id' => $group->id]),
                            'actor' => Yii::$app->user->identity,
                        ]
                    )
                        ->setFrom(Yii::$app->params['systemEmail'])
                        ->setTo($user->notificationEmail)
                        ->setSubject(Yii::t('app/mail', 'New group assignment'));
                    Yii::$app->language = $originalLanguage;
                }

                $users[] = $user;
            } catch (AddFailedException $e) {
                $failed[] = new UserAddErrorResource($e->getIdentifier(), $e->getCause());
            }
        }

        // Send mass email notifications
        Yii::$app->mailer->sendMultiple($messages);

        $result = new UsersAddedResource();
        $result->addedUsers = $users;
        $result->failed = $failed;
        return $result;
    }

    /**
     * Removes a student from a group
     * @throws BadRequestHttpException
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     * @throws ServerErrorHttpException
     * @throws Throwable
     * @throws StaleObjectException
     *
     * @OA\Delete(
     *     path="/instructor/groups/{groupID}/students/{userID}",
     *     operationId="instructor::GroupsController::actionDeleteStudent",
     *     tags={"Instructor Groups"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *           name="groupID",
     *           in="path",
     *           required=true,
     *           description="ID of the group",
     *           @OA\Schema(ref="#/components/schemas/int_id"),
     *     ),
     *     @OA\Parameter(
     *          name="userID",
     *          in="path",
     *          required=true,
     *          description="ID of the student",
     *          @OA\Schema(ref="#/components/schemas/int_id"),
     *    ),
     *    @OA\Response(
     *         response=204,
     *         description="student deleted from the group",
     *    ),
     *    @OA\Response(response=400, ref="#/components/responses/400"),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=403, ref="#/components/responses/403"),
     *    @OA\Response(response=404, ref="#/components/responses/404"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * ),
     */
    public function actionDeleteStudent(int $groupID, int $userID): void
    {
        // Grab the student entry.
        $subscription = Subscription::findOne(
            [
                'groupID' => $groupID,
                'userID' => $userID

            ]);

        // Check if the subscription exists
        if (is_null($subscription)) {
            throw new NotFoundHttpException(Yii::t('app','Subscription not found for the given groupID, userID pair.'));
        }

        // Authorization check
        if (!Yii::$app->user->can('manageGroup', ['groupID' => $subscription->groupID])) {
            throw new ForbiddenHttpException(Yii::t('app', 'You must be an instructor of the group to perform this action!'));
        }

        // Canvas synchronization check
        if ($subscription->group->isCanvasCourse) {
            throw new BadRequestHttpException(Yii::t('app', 'This operation cannot be performed on a canvas synchronized course!'));
        }

        // Check semester
        if ($subscription->semesterID !== SemesterResource::getActualID()) {
            throw new BadRequestHttpException(
                Yii::t('app', "You can't modify a group from a previous semester!")
            );
        }

        // Get uploaded files for the student
        $thereAreUploadedFiles = $subscription->user->getFiles()
            ->where(
                [
                    'taskID' => array_map(
                        static fn (Task $t) => $t->id,
                        Task::findAll(['groupID' => $subscription->groupID])
                    )
                ]
            )
            ->andWhere(['not', ['isAccepted' => StudentFile::IS_ACCEPTED_NO_SUBMISSION]])
            ->exists();

        // Check for uploaded file
        if ($thereAreUploadedFiles) {
            throw new BadRequestHttpException(Yii::t('app', 'Cannot remove student with uploaded file.'));
        }

        // Query all 'No Submission' submissions for the student
        $noSubmissionStudentFiles = $subscription->user->getFiles()
            ->where(
                [
                    'taskID' => array_map(
                        static fn (Task $t) => $t->id,
                        Task::findAll(['groupID' => $subscription->groupID])
                    )
                ]
            )
            ->andWhere(['isAccepted' => StudentFile::IS_ACCEPTED_NO_SUBMISSION])
            ->all();

        // Delete (no submission) student files
        foreach ($noSubmissionStudentFiles as $file) {
            $file->delete();
        }

        // Remove user from task repos if the task is version controlled
        if (Yii::$app->params['versionControl']['enabled']) {
            foreach ($subscription->group->tasks as $task) {
                if ($task->isVersionControlled) {
                    GitManager::removeUserFromTaskRepository($task->id, $subscription->user->userCode);
                }
            }
        }

        if ($subscription->delete()) {
            $this->response->statusCode = 204;
        } else {
            throw new ServerErrorHttpException(
                Yii::t('app', 'Can not remove student. Message: ')
                . Yii::t('app', 'A database error occurred'));
        }
    }

    /**
     * Adds a note to a student
     * @return NotesResource|array
     * @throws BadRequestHttpException
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     * @throws ServerErrorHttpException
     * @OA\Put(
     *     path="/instructor/groups/{groupID}/students/{userID}/notes",
     *     operationId="instructor::GroupsController::actionAddStudentNotes",
     *     tags={"Instructor Groups"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *           name="groupID",
     *           in="path",
     *           required=true,
     *           description="ID of the group",
     *           @OA\Schema(ref="#/components/schemas/int_id"),
     *     ),
     *     @OA\Parameter(
     *          name="userID",
     *          in="path",
     *          required=true,
     *          description="ID of the student",
     *          @OA\Schema(ref="#/components/schemas/int_id"),
     *    ),
     *    @OA\RequestBody(
     *        description="notes updated on student",
     *        @OA\MediaType(
     *            mediaType="application/json",
     *            @OA\Schema(ref="#/components/schemas/Instructor_NotesResource_ScenarioDefault"),
     *        ),
     *    ),
     *    @OA\Response(
     *         response=200,
     *         description="notes updated on student",
     *    ),
     *    @OA\Response(response=400, ref="#/components/responses/400"),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=403, ref="#/components/responses/403"),
     *    @OA\Response(response=404, ref="#/components/responses/404"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * ),
     */
    public function actionAddStudentNotes(int $groupID, int $userID)
    {
        // Grab the student entry.
        $subscription = Subscription::findOne(
            [
                'groupID' => $groupID,
                'userID' => $userID

            ]);
        // Check if the subscription exists
        if (is_null($subscription)) {
            throw new NotFoundHttpException(Yii::t('app','Subscription not found for the given groupID, userID pair.'));
        }

        // Authorization check
        if (!Yii::$app->user->can('manageGroup', ['groupID' => $subscription->groupID])) {
            throw new ForbiddenHttpException(Yii::t('app', 'You must be an instructor of the group to perform this action!'));
        }

        // Check semester
        if ($subscription->semesterID !== SemesterResource::getActualID()) {
            throw new BadRequestHttpException(
                Yii::t('app', "You can't modify notes from a previous semester!")
            );
        }

        $model = new NotesResource();

        $model->load(Yii::$app->request->post(), '');

        if (!$model->validate()) {
            $this->response->statusCode = 422;
            return $model->errors;
        }

        $subscription->notes = $model->notes;

        if ($subscription->save()) {
            // E-mail notification
            if ($subscription->user->notificationEmail) {
                $originalLanguage = Yii::$app->language;
                Yii::$app->language = $subscription->user->locale;
                Yii::$app->mailer->compose('student/updatedPersonalNotes', [
                    'subscription' => $subscription,
                ])
                    ->setFrom(Yii::$app->params['systemEmail'])
                    ->setTo($subscription->user->notificationEmail)
                    ->setSubject(Yii::t('app/mail', 'New notes'))
                    ->send();
                Yii::$app->language = $originalLanguage;
            }
            return $model;
        } elseif ($subscription->hasErrors()) {
            $this->response->statusCode = 422;
            return $subscription->errors;
        } else {
            throw new ServerErrorHttpException(
                Yii::t('app', 'Failed to update note on subscription. Message: ')
                . Yii::t('app', 'A database error occurred'));
        }
    }

    /**
     * Get the notes of a student
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     *
     * @OA\Get(
     *     path="/instructor/groups/{groupID}/students/{userID}/notes",
     *     operationId="instructor::GroupsController::actionStudentNotes",
     *     tags={"Instructor Groups"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *           name="groupID",
     *           in="path",
     *           required=true,
     *           description="ID of the group",
     *           @OA\Schema(ref="#/components/schemas/int_id"),
     *     ),
     *     @OA\Parameter(
     *          name="userID",
     *          in="path",
     *          required=true,
     *          description="ID of the student",
     *          @OA\Schema(ref="#/components/schemas/int_id"),
     *    ),
     *    @OA\Response(
     *         response=200,
     *         description="successful operation",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(ref="#/components/schemas/Instructor_NotesResource_Read"),
     *        ),
     *    ),
     *    @OA\Response(response=400, ref="#/components/responses/400"),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=403, ref="#/components/responses/403"),
     *    @OA\Response(response=404, ref="#/components/responses/404"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * ),
     */
    public function actionStudentNotes(int $groupID, int $userID): NotesResource
    {
        // Grab the student entry.
        $subscription = Subscription::findOne(
            [
                'groupID' => $groupID,
                'userID' => $userID

            ]);

        // Check if the subscription exists
        if (is_null($subscription)) {
            throw new NotFoundHttpException(Yii::t('app','Subscription not found for the given groupID, userID pair.'));
        }

        // Authorization check
        if (!Yii::$app->user->can('manageGroup', ['groupID' => $subscription->groupID])) {
            throw new ForbiddenHttpException(Yii::t('app', 'You must be an instructor of the group to perform this action!'));
        }

        $model = new NotesResource();

        $model->notes = $subscription->notes;

        return $model;
    }

    /**
     * Get mandatory data for group statistics
     * @param int $groupID is the id of the group.
     * @return GroupTaskStatsResource[]
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     *
     * @OA\Get(
     *    path="/instructor/groups/{groupID}/stats",
     *    operationId="instructor::GroupsController::actionGroupStats",
     *    tags={"Instructor Groups"},
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *        name="groupID",
     *        in="path",
     *        required=true,
     *        description="ID of the group",
     *        @OA\Schema(ref="#/components/schemas/int_id")
     *    ),
     *    @OA\Response(
     *       response=200,
     *       description="successful operation",
     *       @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Instructor_GroupTaskStatsResource_Read")),
     *    ),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=403, ref="#/components/responses/403"),
     *    @OA\Response(response=404, ref="#/components/responses/404"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * ),
     */
    public function actionGroupStats(int $groupID): array
    {
        $group = GroupResource::findOne($groupID);

        if (is_null($group)) {
            throw new NotFoundHttpException("Group not found");
        }

        // Authorization check
        if (!Yii::$app->user->can('manageGroup', ['groupID' => $groupID])) {
            throw new ForbiddenHttpException(Yii::t('app', 'You must be an instructor of the group to perform this action!'));
        }

        $countStudentsInGroup = $group->getSubscriptions()->count();

        /** @var GroupTaskStatsResource[] */
        $stats = [];

        foreach ($group->tasks as $task) {
            $submittedInTime = 0;
            $submittedDelayed = 0;
            $submittedNot = $countStudentsInGroup;

            $groupScores = [];

            foreach ($task->studentFiles as $studentFile) {
                if($studentFile->isAccepted !== StudentFile::IS_ACCEPTED_NO_SUBMISSION) {
                    if ($task->softDeadline != null) {
                        if ($studentFile->uploadTime <= $task->softDeadline) {
                            $submittedInTime += 1;
                            $submittedNot -= 1;
                        } else {
                            $submittedDelayed += 1;
                            $submittedNot -= 1;
                        }
                    } else {
                        if ($studentFile->uploadTime <= $task->hardDeadline) {
                            $submittedInTime += 1;
                            $submittedNot -= 1;
                        }
                    }
                }
                if (!is_null($studentFile->grade)) {
                    $groupScores[] = $studentFile->grade;
                }
            }

            $submittedMissed = 0;
            if (strtotime($task->hardDeadline) < time()) {
                $submittedMissed = $submittedNot;
            }

            $submitted = new GroupSubmittedStatsResource();
            $submitted->intime = (int)$submittedInTime;
            $submitted->delayed = (int)$submittedDelayed;
            $submitted->missed = (int)$submittedMissed;

            $taskStats = new GroupTaskStatsResource();
            $taskStats->taskID = $task->id;
            $taskStats->name = $task->name;
            $taskStats->points = $groupScores;
            $taskStats->submitted = $submitted;

            $stats[] = $taskStats;
        }

        return $stats;
    }

    /**
     * Get mandatory data for student statistics
     * @param int $groupID is the id of the group.
     * @param int $studentID is the id of the student.
     * @return StudentStatsResource[]
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     *
     * @OA\Get(
     *     path="/instructor/groups/{groupID}/students/{studentID}/stats",
     *     operationId="instructor::GroupsController::actionStudentStats",
     *     tags={"Instructor Groups"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="groupID",
     *         in="path",
     *         required=true,
     *         description="ID of the group",
     *         @OA\Schema(ref="#/components/schemas/int_id")
     *     ),
     *     @OA\Parameter(
     *         name="studentID",
     *         in="path",
     *         required=true,
     *         description="userID of the student",
     *         @OA\Schema(ref="#/components/schemas/int_id")
     *     ),
     *     @OA\Response(
     *        response=200,
     *        description="successful operation",
     *        @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Instructor_StudentStatsResource_Read")),
     *    ),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=403, ref="#/components/responses/403"),
     *    @OA\Response(response=404, ref="#/components/responses/404"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * ),
     */
    public function actionStudentStats(int $groupID, int $studentID): array
    {
        $student = UserResource::findOne($studentID);

        if (is_null($student)) {
            throw new NotFoundHttpException("Student not found");
        }

        $group = GroupResource::findOne($groupID);

        if (is_null($group)) {
            throw new NotFoundHttpException("Group not found");
        }

        // Authorization check
        if (!Yii::$app->user->can('manageGroup', ['groupID' => $groupID])) {
            throw new ForbiddenHttpException(Yii::t('app', 'You must be an instructor of the group to perform this action!'));
        }

        /** @var StudentStatsResource[] */
        $stats = [];

        foreach ($group->tasks as $task) {
            $userScore = null;
            $groupScores = [];
            $submittingTime = null;
            foreach ($task->studentFiles as $studentFile) {
                if ($studentFile->uploaderID == $student->id && $studentFile->isAccepted !== StudentFile::IS_ACCEPTED_NO_SUBMISSION) {
                    $userScore = $studentFile->grade;
                    $submittingTime = $studentFile->uploadTime;
                }
                if ($studentFile->grade != null) {
                    $groupScores[] = $studentFile->grade;
                }
            }

            $studentStats = new StudentStatsResource();
            $studentStats->taskID = $task->id;
            $studentStats->name = $task->name;
            $studentStats->submittingTime = $submittingTime;
            $studentStats->softDeadLine = $task->softDeadline;
            $studentStats->hardDeadLine = $task->hardDeadline;
            $studentStats->user = $userScore;
            $studentStats->username = $student->name;
            $studentStats->group = $groupScores;

            $stats[] = $studentStats;
        }

        return $stats;
    }
}
