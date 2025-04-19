<?php

namespace app\modules\admin\controllers;

use app\exceptions\AddFailedException;
use app\models\Course;
use app\models\CourseCode;
use app\models\InstructorCourse;
use app\models\User;
use app\modules\admin\resources\CreateUpdateCourseResource;
use app\resources\CourseResource;
use app\resources\UserAddErrorResource;
use app\resources\UserResource;
use app\resources\UsersAddedResource;
use Exception;
use Yii;
use yii\data\ActiveDataProvider;
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
    public function actions(): array
    {
        $actions = parent::actions();
        $actions['index']['prepareDataProvider'] = [$this, 'prepareDataProvider'];
        unset($actions['delete']);
        unset($actions['create']);
        unset($actions['update']);
        return $actions;
    }

    public function prepareDataProvider(): ActiveDataProvider
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
            $resource->scenario = CreateUpdateCourseResource::SCENARIO_CREATE;
            $resource->load(Yii::$app->request->post(), '');
            if (!$resource->validate()) {
                $this->response->statusCode = 422;
                return $resource->errors;
            }

            $course = new CourseResource();
            $course->name = $resource->name;
            $validationErrors = $this->saveCourse($course, $resource->codes);
            if (!empty($validationErrors)) {
                $this->response->statusCode = 422;
                return $validationErrors;
            }

            $addedLecturersResult = $this->processLecturers($resource->lecturerUserCodes, $course->id);
            if (!empty($addedLecturersResult->failed)) {
                $transaction->rollBack();
                $this->response->statusCode = 422;
                return ['lecturerUserCodes' => $addedLecturersResult->convertFailedToStringArray()];
            }
            $transaction->commit();
            $this->sendEmailToAddedUsers($addedLecturersResult->addedUsers, $course->id);

            $this->response->statusCode = 201;
            return $course;
        } catch (Exception $e) {
            $transaction->rollBack();
            throw new ServerErrorHttpException(Yii::t('app', "Couldn't save new course."));
        }
    }

    private function saveCourse(Course $course, array $codes): array
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
