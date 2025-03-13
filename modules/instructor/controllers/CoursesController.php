<?php

namespace app\modules\instructor\controllers;

use app\controllers\BaseRestController;
use app\exceptions\AddFailedException;
use app\models\Course;
use app\models\CourseCode;
use app\models\Group;
use app\models\User;
use app\modules\admin\resources\CreateUpdateCourseResource;
use app\resources\AddUsersListResource;
use app\resources\CourseResource;
use app\resources\UserAddErrorResource;
use app\resources\UserResource;
use app\resources\UsersAddedResource;
use app\models\InstructorCourse;
use Exception;
use Throwable;
use Yii;
use yii\data\ArrayDataProvider;
use yii\db\StaleObjectException;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;

/**
 * This class provides access to courses for instructors and admins
 */
class CoursesController extends BaseInstructorRestController
{
    protected function verbs(): array
    {
        return ArrayHelper::merge(
            parent::verbs(),
            [
                'index' => ['GET'],
                'view' => ['GET'],
                'list-lecturers' => ['GET'],
                'addLecturer' => ['POST'],
                'removeLecturer' => ['DELETE'],
            ]
        );
    }

    /**
     * Lists available courses for the current user
     *
     *  @OA\Get(
     *     path="/instructor/courses",
     *     operationId="instructor::CoursesController::actionIndex",
     *     tags={"Instructor Courses"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="instuctor",
     *         in="query",
     *         required=false,
     *         description="List courses where the current user is a lecturer",
     *         explode=true,
     *         @OA\Schema(
     *             type="boolean",
     *             default=true
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="forGroups",
     *         in="query",
     *         required=false,
     *         description="List courses where the current user is a group instructor",
     *         explode=true,
     *         @OA\Schema(
     *             type="boolean",
     *             default=false
     *         )
     *     ),
     *     @OA\Parameter(ref="#/components/parameters/yii2_fields"),
     *     @OA\Parameter(ref="#/components/parameters/yii2_expand"),
     *     @OA\Parameter(ref="#/components/parameters/yii2_sort"),
     *     @OA\Response(
     *         response=200,
     *         description="successful operation",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Common_CourseResource_Read")),
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/401"),
     *     @OA\Response(response=500, ref="#/components/responses/500"),
     * )
     */
    public function actionIndex(bool $instructor = true, bool $forGroups = false): ArrayDataProvider
    {
        // Collect unique courses
        $courseMap = [];
        // Get courses from InstructorCourses
        if ($instructor) {
            /** @var InstructorCourse[] $instructorCourses */
            $instructorCourses = InstructorCourse::find()->where(['userID' => Yii::$app->user->id])->all();
            foreach ($instructorCourses as $ic) {
                $course = $ic->course;
                $courseMap[$course->id] = new CourseResource($course);
            }
        }

        // Get courses from InstructorGroups
        if ($forGroups) {
            foreach (Group::find()->joinWith('instructorGroups')->where(['userID' => Yii::$app->user->id])->all() as $ig) {
                $course = $ig->course;
                $courseMap[$course->id] = new CourseResource($course);
            }
        }

        // Convert key-value pairs into a regular array
        $courseList = [];
        foreach ($courseMap as $course) {
            $courseList[] = $course;
        }

        return new ArrayDataProvider([
            'allModels' => $courseList,
            'modelClass' => CourseResource::class,
            'pagination' => false
        ]);
    }

    /**
     * View course
     * @throws NotFoundHttpException
     * @throws ForbiddenHttpException
     *
     * @OA\Get(
     *     path="/instructor/courses/{id}",
     *     operationId="instructor::CoursesController::actionView",
     *     tags={"Instructor Courses"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(ref="#/components/parameters/yii2_fields"),
     *      @OA\Parameter(ref="#/components/parameters/yii2_expand"),
     *      @OA\Response(
     *          response=200,
     *          description="successful operation",
     *          @OA\JsonContent(ref="#/components/schemas/Instructor_QuizQuestionSetResource_Read"),
     *      ),
     *     @OA\Response(response=400, ref="#/components/responses/400"),
     *     @OA\Response(response=401, ref="#/components/responses/401"),
     *     @OA\Response(response=404, ref="#/components/responses/404"),
     *     @OA\Response(response=500, ref="#/components/responses/500"),
     * )
 */
    public function actionView(int $id): CourseResource
    {
        $course = CourseResource::findOne($id);

        if (is_null($course)) {
            throw new NotFoundHttpException(Yii::t('app', 'Course not found.'));
        }

        // Authorization check
        if (!Yii::$app->user->can('manageCourse', ['courseID' => $id])) {
            throw new ForbiddenHttpException(
                Yii::t('app', 'You must be an admin or a lecturer of the course to perform this action!')
            );
        }

        return $course;
    }

    /**
     * List lecturers for the given course
     * @throws NotFoundHttpException
     * @throws ForbiddenHttpException
     *
     * @OA\Get(
     *     path="/instructor/courses/{courseID}/lecturers",
     *     operationId="instructor::CoursesController::actionListLecturers",
     *     tags={"Instructor Courses"},
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
     *    @OA\Response(response=404, ref="#/components/responses/404"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * ),
     */
    public function actionListLecturers(int $courseID): ArrayDataProvider
    {
        $course = CourseResource::findOne($courseID);

        if (is_null($course)) {
            throw new NotFoundHttpException(Yii::t('app', 'Course not found'));
        }

        // Authorization check
        if (!Yii::$app->user->can('manageCourse', ['courseID' => $courseID])) {
            throw new ForbiddenHttpException(
                Yii::t('app', 'You must be an admin or a lecturer of the course to perform this action!')
            );
        }

        $instructorCourses = $course->instructorCourses;
        $users = [];
        foreach ($instructorCourses as $ic) {
            $users[] = new UserResource($ic->user);
        }

        return new ArrayDataProvider(
            [
                'allModels' => $users,
                'modelClass' => UserResource::class,
                'pagination' => false
            ]
        );
    }

    /**
     * Add lecturers to a course
     * @return UsersAddedResource|array
     * @throws NotFoundHttpException
     * @throws ForbiddenHttpException
     * @OA\Post(
     *     path="/instructor/courses/{courseID}/lecturers",
     *     operationId="instructor::CoursesController::actionAddLecturers",
     *     tags={"Instructor Courses"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         description="list of lecturers",
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
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=404, ref="#/components/responses/404"),
     *    @OA\Response(response=422, ref="#/components/responses/422"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * ),
     */
    public function actionAddLecturers(int $courseID)
    {
        $course = CourseResource::findOne($courseID);

        if (is_null($course)) {
            throw new NotFoundHttpException(Yii::t('app', 'Course not found'));
        }

        // Authorization check
        if (!Yii::$app->user->can('manageCourse', ['courseID' => $courseID])) {
            throw new ForbiddenHttpException(
                Yii::t('app', 'You must be an admin or a lecturer of the course to perform this action!')
            );
        }

        $model = new AddUsersListResource();
        $model->load(Yii::$app->request->post(), '');
        if ($model->validate()) {
            $this->response->statusCode = 207;
            $result = $this->processLecturers($model->userCodes, $courseID);
            $this->sendEmailToAddedUsers($result->addedUsers, $courseID);
            return $result;
        } else {
            $this->response->statusCode = 422;
            return $model->errors;
        }
    }

    /**
     * Tries to add new lecturers to the given course
     * @param string[] $userCodes
     * @param int $courseID
     */
    private function processLecturers(array $userCodes, int $courseID): UsersAddedResource
    {
        // Email notifications
        $users = [];
        $failed = [];

        foreach ($userCodes as $userCode) {
            try {
                $user = UserResource::findOne(['userCode' => $userCode]);

                if (is_null($user)) {
                    throw new AddFailedException($userCode, ['userCode' => [Yii::t('app', 'User not found.')]]);
                }

                // Add the lecturer to the group.
                $instructorCourse = new InstructorCourse(
                    [
                        'userID' => $user->id,
                        'courseID' => $courseID,
                    ]
                );

                if (!$instructorCourse->save()) {
                    throw new AddFailedException($userCode, $instructorCourse->errors);
                }

                // Assign faculty role if necessary
                $authManager = Yii::$app->authManager;
                if (!$authManager->checkAccess($user->id, 'faculty')) {
                    $authManager->assign($authManager->getRole('faculty'), $user->id);
                }

                $users[] = $user;
            } catch (AddFailedException $e) {
                $failed[] = new UserAddErrorResource($e->getIdentifier(), $e->getCause());
            }
        }

        $resource = new UsersAddedResource();
        $resource->addedUsers = $users;
        $resource->failed = $failed;
        return $resource;
    }

    /**
     * Sends notification to the lecturer after they are added to a course
     * @param User[] $users
     * @return void
     */
    private function sendEmailToAddedUsers(array $users, int $courseID): void
    {
        $messages = [];

        foreach ($users as $user) {
            if (!empty($user->notificationEmail)) {
                $originalLanguage = Yii::$app->language;

                Yii::$app->language = $user->locale;
                $messages[] = Yii::$app->mailer->compose(
                    'instructor/newCourse',
                    [
                        'course' => Course::findOne(['id' => $courseID]),
                        'actor' => Yii::$app->user->identity,
                    ]
                )
                    ->setFrom(Yii::$app->params['systemEmail'])
                    ->setTo($user->notificationEmail)
                    ->setSubject(Yii::t('app/mail', 'Added to new course'));
                Yii::$app->language = $originalLanguage;
            }
        }

        Yii::$app->mailer->sendMultiple($messages);
    }

    /**
     * Remove a lecturer from the given course
     * @throws NotFoundHttpException
     * @throws ServerErrorHttpException
     * @throws Throwable
     * @throws StaleObjectException
     *
     * @OA\Delete(
     *     path="/instructor/courses/{courseID}/lecturers/{userID}",
     *     operationId="instructor::CoursesController::actionDeleteLecturer",
     *     tags={"Instructor Courses"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *           name="courseID",
     *           in="path",
     *           required=true,
     *           description="ID of the course",
     *           @OA\Schema(ref="#/components/schemas/int_id"),
     *     ),
     *     @OA\Parameter(
     *          name="userID",
     *          in="path",
     *          required=true,
     *          description="ID of the lecturer",
     *          @OA\Schema(ref="#/components/schemas/int_id"),
     *    ),
     *    @OA\Response(
     *         response=204,
     *         description="lecturer deleted from the course",
     *     ),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=404, ref="#/components/responses/404"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * ),
     */
    public function actionDeleteLecturer(int $courseID, int $userID): void
    {
        $ic = InstructorCourse::findOne(['courseID' => $courseID, 'userID' => $userID]);

        // Authorization check
        if (!Yii::$app->user->can('manageCourse', ['courseID' => $courseID])) {
            throw new ForbiddenHttpException(
                Yii::t('app', 'You must be an admin or a lecturer of the course to perform this action!')
            );
        }

        if (is_null($ic)) {
            throw new NotFoundHttpException(Yii::t('app', 'This user and course combination not found'));
        }

        $course = Course::findOne(['id' => $courseID]);

        if (count($course->getLecturers()->all()) <= 1) {
            throw new BadRequestHttpException(Yii::t('app', 'Cannot remove last lecturer!'));
        }

        if ($ic->delete()) {
            $this->response->statusCode = 204;
        } else {
            throw new ServerErrorHttpException(Yii::t('app', 'A database error occurred'));
        }
    }

    /**
     * @OA\Put(
     *      path="/instructor/courses/{id}",
     *      operationId="instructor::CoursesController::actionUpdate",
     *      summary="Update a course",
     *      tags={"Instructor Courses"},
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(ref="#/components/parameters/yii2_fields"),
     *      @OA\Parameter(ref="#/components/parameters/yii2_expand"),
     *      @OA\RequestBody(
     *          description="updated course",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(ref="#/components/schemas/Common_CourseResource_ScenarioDefault"),
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="course updated",
     *          @OA\JsonContent(ref="#/components/schemas/Common_CourseResource_Read"),
     *      ),
     *     @OA\Response(response=401, ref="#/components/responses/401"),
     *     @OA\Response(response=404, ref="#/components/responses/404"),
     *     @OA\Response(response=422, ref="#/components/responses/422"),
     *     @OA\Response(response=500, ref="#/components/responses/500"),
     *  ),
     * @param int $id
     * @return Course|null|array
     * @throws ServerErrorHttpException
     * @throws ForbiddenHttpException
     */
    public function actionUpdate(int $id)
    {
        // Authorization check
        if (!Yii::$app->user->can('manageCourse', ['courseID' => $id])) {
            throw new ForbiddenHttpException(
                Yii::t('app', 'You must be an admin or a lecturer of the course to perform this action!')
            );
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $resource = new CreateUpdateCourseResource();
            $resource->scenario = CreateUpdateCourseResource::SCENARIO_UPDATE;
            $resource->load(Yii::$app->request->post(), '');
            if (!$resource->validate()) {
                $this->response->statusCode = 422;
                return $resource->errors;
            }

            $course = CourseResource::findOne(['id' => $id]);
            $course->name = $resource->name;
            $validationErrors = $this->saveCourse($course, $resource->codes);
            if (!empty($validationErrors)) {
                $this->response->statusCode = 422;
                return $validationErrors;
            }

            $transaction->commit();
            return $course;
        } catch (Exception $e) {
            $transaction->rollBack();
            throw new ServerErrorHttpException(Yii::t('app', "Couldn't update course."));
        }
    }

    private function saveCourse(Course $course, array $codes): ?array
    {
        if ($course->save()) {
            CourseCode::deleteAll(['courseId' => $course->id]);
            foreach ($codes as $code) {
                $courseCode = new CourseCode();
                $courseCode->courseId = $course->id;
                $courseCode->code = $code;
                if (!$courseCode->save()) {
                    return $courseCode->errors;
                }
            }
        } else {
            return $course->errors;
        }
        return [];
    }
}
