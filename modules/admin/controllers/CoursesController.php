<?php

namespace app\modules\admin\controllers;

use app\exceptions\AddFailedException;
use app\models\Course;
use app\models\CourseCode;
use app\models\InstructorCourse;
use app\modules\admin\resources\CreateUpdateCourseResource;
use app\resources\AddUsersListResource;
use app\resources\CourseResource;
use app\resources\UserAddErrorResource;
use app\resources\UserResource;
use app\resources\UsersAddedResource;
use Exception;
use Throwable;
use Yii;
use yii\data\ActiveDataProvider;
use yii\data\ArrayDataProvider;
use yii\db\StaleObjectException;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;

/**
 * @OA\PathItem(
 *   path="/admin/courses/{id}",
 *   @OA\Parameter(
 *      name="id",
 *      in="path",
 *      required=true,
 *      description="ID of the course",
 *      @OA\Schema(ref="#/components/schemas/int_id"),
 *   ),
 * ),
 * @OA\PathItem(
 *   path="/admin/courses/{courseID}/lecturers",
 *   @OA\Parameter(
 *      name="courseID",
 *      in="path",
 *      required=true,
 *      description="ID of the course",
 *      @OA\Schema(ref="#/components/schemas/int_id"),
 *   ),
 * )
 */

/**
 * Controller class for managing courses
 */
class CoursesController extends BaseAdminActiveController
{
    public $modelClass = CourseResource::class;

    /**
     * @inheritdoc
     */
    protected function verbs()
    {
        return array_merge(parent::verbs(), [
            'list-lecturers' => ['GET'],
            'add-lecturers' => ['POST'],
            'remove-lecturer' => ['DELETE'],
        ]);
    }

    /**
     * @inheritdoc
     */
    public function actions()
    {
        $actions = parent::actions();
        $actions['index']['prepareDataProvider'] = [$this, 'prepareDataProvider'];
        unset($actions['delete']);
        unset($actions['create']);
        unset($actions['update']);
        return $actions;
    }

    public function prepareDataProvider()
    {
        return new ActiveDataProvider(
            [
                'query' => $this->modelClass::find(),
                'pagination' => false,
                'sort' => false,
            ]
        );
    }

    /**
     * List lecturers for the given course
     * @throws NotFoundHttpException
     *
     * @OA\Get(
     *     path="/admin/courses/{courseID}/lecturers",
     *     operationId="admin::CoursesController::actionListLecturers",
     *     tags={"Admin Courses"},
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
     * @OA\Post(
     *     path="/admin/courses/{courseID}/lecturers",
     *     operationId="admin::CoursesController::actionAddLecturers",
     *     tags={"Admin Courses"},
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

        $model = new AddUsersListResource();
        $model->load(Yii::$app->request->post(), '');
        if ($model->validate()) {
            $this->response->statusCode = 207;
            return $this->processLecturers($model->userCodes, $courseID);
        } else {
            $this->response->statusCode = 422;
            return $model->errors;
        }
    }

    /**
     * @param string[] $userCodes
     * @param int $courseID
     */
    private function processLecturers(array $userCodes, int $courseID): UsersAddedResource
    {
        // Email notifications
        $messages = [];
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
            } catch (AddFailedException $e) {
                $failed[] = new UserAddErrorResource($e->getIdentifier(), $e->getCause());
            }
        }
        // Send mass email notifications
        Yii::$app->mailer->sendMultiple($messages);

        $resource = new UsersAddedResource();
        $resource->addedUsers = $users;
        $resource->failed = $failed;
        return $resource;
    }

    /**
     * Remove a lecturer from the given course
     * @throws NotFoundHttpException
     * @throws ServerErrorHttpException
     * @throws Throwable
     * @throws StaleObjectException
     *
     * @OA\Delete(
     *     path="/admin/courses/{courseID}/lecturers/{userID}",
     *     operationId="admin::CoursesController::actionDeleteLecturer",
     *     tags={"Admin Courses"},
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

        if (is_null($ic)) {
            throw new NotFoundHttpException(Yii::t('app', 'This user and course combination not found'));
        }

        if ($ic->delete()) {
            $this->response->statusCode = 204;
        } else {
            throw new ServerErrorHttpException(Yii::t('app', 'A database error occurred'));
        }
    }


    /**
     * @return Course|null|array
     * @throws ServerErrorHttpException
     * @OA\Post(
     *      path="/admin/courses",
     *      operationId="admin::CoursesController::actionCreate",
     *      summary="Create a new course",
     *      tags={"Admin Courses"},
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(ref="#/components/parameters/yii2_fields"),
     *      @OA\Parameter(ref="#/components/parameters/yii2_expand"),
     *      @OA\RequestBody(
     *          description="new course",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(ref="#/components/schemas/Common_CourseResource_ScenarioDefault"),
     *          )
     *      ),
     *      @OA\Response(
     *          response=201,
     *          description="new course created",
     *          @OA\JsonContent(ref="#/components/schemas/Common_CourseResource_Read"),
     *      ),
     *     @OA\Response(response=401, ref="#/components/responses/401"),
     *     @OA\Response(response=422, ref="#/components/responses/422"),
     *     @OA\Response(response=500, ref="#/components/responses/500"),
     *  ),
     */
    public function actionCreate()
    {
        $transaction = Yii::$app->db->beginTransaction();

        try {
            $resource = new CreateUpdateCourseResource();
            $resource->load(Yii::$app->request->post(), '');

            $course = new CourseResource();
            $course->name = $resource->name;
            $this->response->statusCode = 201;
            $validationErrors = $this->saveCourse($course, $resource->codes);
            if (!empty($validationErrors)) {
                $this->response->statusCode = 422;
                return $validationErrors;
            }
            $transaction->commit();
            return $course;
        } catch (Exception $e) {
            $transaction->rollBack();
            throw new ServerErrorHttpException(Yii::t('app', "Couldn't save new course."));
        }
    }

   /**
     * @OA\Put(
     *      path="/admin/courses/{id}",
     *      operationId="admin::CoursesController::actionUpdate",
     *      summary="Update a course",
     *      tags={"Admin Courses"},
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
     */

    public function actionUpdate(int $id)
    {
        $transaction = Yii::$app->db->beginTransaction();
        try {
            $resource = new CreateUpdateCourseResource();
            $resource->load(Yii::$app->request->post(), '');

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

    private function saveCourse(Course $course, array $codes) : ?array
    {
        if ($course->save()) {
            CourseCode::deleteAll(['courseId' => $course->id]);
            foreach ($codes as $code) {
                $courseCode = new CourseCode();
                $courseCode->courseId = $course->id;
                $courseCode->code = $code;
                if (!$courseCode->save()) {
                    $this->response->statusCode = 422;
                    return $courseCode->errors;
                }
            }
        } else {
            $this->response->statusCode = 422;
            return $course->errors;
        }
        return [];
    }

    /**
     * Annotate ActiveController actions
     *
     * @OA\Get(
     *     path="/admin/courses",
     *     operationId="admin::CoursesController::actionIndex",
     *     summary="List courses",
     *     tags={"Admin Courses"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(ref="#/components/parameters/yii2_fields"),
     *     @OA\Parameter(ref="#/components/parameters/yii2_expand"),
     *     @OA\Parameter(ref="#/components/parameters/yii2_sort"),
     *
     *     @OA\Response(
     *        response=200,
     *        description="successful operation",
     *        @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Common_CourseResource_Read")),
     *    ),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * ),
     *
     * @OA\Get(
     *     path="/admin/courses/{id}",
     *     operationId="admin::CoursesController::actionView",
     *     summary="View a course",
     *     tags={"Admin Courses"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(ref="#/components/parameters/yii2_fields"),
     *     @OA\Parameter(ref="#/components/parameters/yii2_expand"),
     *     @OA\Response(
     *         response=200,
     *         description="successful operation",
     *         @OA\JsonContent(ref="#/components/schemas/Common_CourseResource_Read"),
     *     ),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=404, ref="#/components/responses/404"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * ),
     */
}
