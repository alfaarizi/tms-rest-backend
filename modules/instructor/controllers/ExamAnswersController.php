<?php

namespace app\modules\instructor\controllers;

use app\models\ExamTestInstanceQuestion;
use app\modules\instructor\resources\ExamAnswerResource;
use Yii;
use app\modules\instructor\resources\ExamQuestionResource;
use app\resources\SemesterResource;
use yii\data\ActiveDataProvider;
use yii\helpers\ArrayHelper;
use yii\web\BadRequestHttpException;
use yii\web\ConflictHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;

/**
 * This class provides access to exam answers for instructors
 */
class ExamAnswersController extends BaseInstructorRestController
{
    protected function verbs()
    {
        return ArrayHelper::merge(
            parent::verbs(),
            [
                'index' => ['GET'],
                'create' => ['POST'],
                'delete' => ['DELETE'],
                'update' => ['PATCH', 'PUT'],
            ]
        );
    }

    public function actionIndex($questionID)
    {
        $question = ExamQuestionResource::findOne($questionID);
        if (is_null($question)) {
            throw new NotFoundHttpException(Yii::t('app', 'Question does not exist'));
        }

        if (!Yii::$app->user->can('manageGroup', [
            'courseID' => $question->questionSet->courseID,
            'semesterID' => SemesterResource::getActualID()])
        ) {
            throw new ForbiddenHttpException(Yii::t('app', 'You must be an incumbent instructor of the course to perform this action!'));
        }

        return new ActiveDataProvider(
            [
                'query' => $question->getAnswers(),
                'sort' => false,
                'pagination' => false,
            ]
        );
    }

    public function actionCreate()
    {
        $answer = new ExamAnswerResource();
        $answer->scenario = ExamAnswerResource::SCENARIO_CREATE;
        $answer->load(Yii::$app->request->post(), '');

        if (!$answer->validate()) {
            $this->response->statusCode = 422;
            return $answer->errors;
        }

        if (!Yii::$app->user->can('manageGroup', [
            'courseID' => $answer->question->questionSet->courseID,
            'semesterID' => SemesterResource::getActualID()])
        ) {
            throw new ForbiddenHttpException(Yii::t('app', 'You must be an incumbent instructor of the course to perform this action!'));
        }

        if (ExamTestInstanceQuestion::findOne(['questionID' => $answer->questionID]) != null) {
            throw new ConflictHttpException(Yii::t('app', 'Cannot add answer because a test that contains it was finalized'));
        }

        if ($answer->save(false)) {
            $this->response->statusCode = 201;
            return $answer;
        } else {
            throw new ServerErrorHttpException(Yii::t('app', 'A database error occurred'));
        }
    }

    /**
     * @param int $id
     * @return ExamAnswerResource
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     * @throws ServerErrorHttpException
     */
    public function actionUpdate($id)
    {
        $answer = ExamAnswerResource::findOne($id);

        if (is_null($answer)) {
            throw new NotFoundHttpException(Yii::t('app', 'Answer does not exist'));
        }

        $answer->scenario = ExamAnswerResource::SCENARIO_UPDATE;
        $answer->load(Yii::$app->request->post(), '');

        if (!$answer->validate()) {
            $this->response->statusCode = 422;
            return $answer->errors;
        }

        if (!Yii::$app->user->can('manageGroup', [
            'courseID' => $answer->question->questionSet->courseID,
            'semesterID' => SemesterResource::getActualID()])
        ) {
            throw new ForbiddenHttpException(Yii::t('app', 'You must be an incumbent instructor of the course to perform this action!'));
        }

        if (ExamTestInstanceQuestion::findOne(['questionID' => $answer->questionID]) != null) {
            throw new ConflictHttpException(Yii::t('app', 'Cannot update answer because a test that contains it was finalized'));
        }

        if ($answer->save(false)) {
            return $answer;
        } else {
            throw new ServerErrorHttpException(Yii::t('app', 'A database error occurred'));
        }
    }

    /**
     * @param int $id
     * @return void|BadRequestHttpException
     * @throws ConflictHttpException
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     * @throws ServerErrorHttpException
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public function actionDelete($id)
    {
        $answer = ExamAnswerResource::findOne($id);

        if (is_null($answer)) {
            throw new NotFoundHttpException(Yii::t('app', 'Answer does not exist'));
        }

        if (!Yii::$app->user->can('manageGroup', [
            'courseID' => $answer->question->questionSet->courseID,
            'semesterID' => SemesterResource::getActualID()])
        ) {
            throw new ForbiddenHttpException(Yii::t('app', 'You must be an incumbent instructor of the course to perform this action!'));
        }

        if (ExamTestInstanceQuestion::findOne(['questionID' => $answer->questionID]) != null) {
            throw new ConflictHttpException(Yii::t('app', 'Cannot delete answer because a test that contains it was finalized'));
        }

        try {
            $answer->delete();
            $this->response->statusCode = 204;
            return;
        } catch (yii\db\IntegrityException $e) {
            throw new ConflictHttpException(Yii::t('app', 'Cannot delete answer because it appears in a test instance'));
        } catch (yii\base\ErrorException $e) {
            throw new ServerErrorHttpException(Yii::t('app', 'A database error occurred'));
        }
    }
}
