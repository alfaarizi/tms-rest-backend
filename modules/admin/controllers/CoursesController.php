<?php

namespace app\modules\admin\controllers;

use app\exceptions\AddFailedException;
use app\models\Course;
use app\models\InstructorCourse;
use app\resources\AddUsersListResource;
use app\resources\CourseResource;
use app\resources\UserAddErrorResource;
use app\resources\UserResource;
use Throwable;
use Yii;
use yii\data\ActiveDataProvider;
use yii\db\StaleObjectException;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;

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
     * @param int $courseID
     * @return array
     * @throws NotFoundHttpException
     */
    public function actionListLecturers($courseID)
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

        return $users;
    }


    /**
     * @param $groupID
     * @return array|array[]
     * @throws BadRequestHttpException
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     */
    public function actionAddLecturers($courseID)
    {
        $course = CourseResource::findOne($courseID);

        if (is_null($course)) {
            throw new NotFoundHttpException(Yii::t('app', 'Course not found'));
        }

        $model = new AddUsersListResource();
        $model->load(Yii::$app->request->post(), '');
        if ($model->validate()) {
            $this->response->statusCode = 207;
            return $this->processLecturers($model->neptunCodes, $courseID);
        } else {
            $this->response->statusCode = 422;
            return $model->errors;
        }
    }

    /**
     * @param $neptunCodes
     * @param $courseID
     * @return array[]
     */
    private function processLecturers($neptunCodes, $courseID)
    {
        // Email notifications
        $messages = [];
        $users = [];
        $failed = [];

        foreach ($neptunCodes as $neptun) {
            try {
                $user = UserResource::findOne(['neptun' => $neptun]);

                if (is_null($user)) {
                    throw new AddFailedException($neptun, ['neptun' => [Yii::t('app', 'User not found found.')]]);
                }

                // Add the lecturer to the group.
                $instructorCourse = new InstructorCourse(
                    [
                        'userID' => $user->id,
                        'courseID' => $courseID,
                    ]
                );

                if (!$instructorCourse->save()) {
                    throw new AddFailedException($neptun, $instructorCourse->errors);
                }

                // Assign faculty role if necessary
                $authManager = Yii::$app->authManager;
                if (!$authManager->checkAccess($user->id, 'faculty')) {
                    $authManager->assign($authManager->getRole('faculty'), $user->id);
                }

                $users[] = $user;
                if (!empty($user->email)) {
                    $originalLanguage = Yii::$app->language;

                    Yii::$app->language = $user->locale;
                    if (!empty($user->email)) {
                        Yii::$app->language = $user->locale;
                        $messages[] = Yii::$app->mailer->compose(
                            'instructor/newCourse',
                            [
                                'course' => Course::findOne(['id' => $courseID]),
                                'actor' => Yii::$app->user->identity,
                            ]
                        )
                            ->setFrom(Yii::$app->params['systemEmail'])
                            ->setTo($user->email)
                            ->setSubject(Yii::t('app/mail', 'New course assignment'));
                    }
                    Yii::$app->language = $originalLanguage;
                }
            } catch (AddFailedException $e) {
                $failed[] = new UserAddErrorResource($e->getIdentifier(), $e->getCause());
            }
        }
        // Send mass email notifications
        Yii::$app->mailer->sendMultiple($messages);

        return [
            'addedUsers' => $users,
            'failed' => $failed
        ];
    }

    /**
     * @param int $courseID
     * @param int $userID
     * @throws NotFoundHttpException
     * @throws ServerErrorHttpException
     * @throws Throwable
     * @throws StaleObjectException
     */
    public function actionDeleteLecturer($courseID, $userID)
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
}
