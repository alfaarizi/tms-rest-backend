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

    /**
     * Get all answers for the given question
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     *
     * @OA\Get(
     *     path="/instructor/exam-answers",
     *     operationId="instructor::ExamAnswersController::actionIndex",
     *     tags={"Instructor Exam Answers"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="questionID",
     *         in="query",
     *         required=true,
     *         description="ID of the question",
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
     *        @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Instructor_ExamAnswerResource_Read")),
     *    ),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=403, ref="#/components/responses/403"),
     *    @OA\Response(response=404, ref="#/components/responses/404"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * ),
     */
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

    /**
     * Create new answer for a question
     * @return ExamAnswerResource|array
     * @throws ConflictHttpException
     * @throws ForbiddenHttpException
     * @throws ServerErrorHttpException
     *
     * @OA\Post(
     *     path="/instructor/exam-answers",
     *     operationId="instructor::ExamAnswersController::actionCreate",
     *     tags={"Instructor Exam Answers"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(ref="#/components/parameters/yii2_fields"),
     *     @OA\Parameter(ref="#/components/parameters/yii2_expand"),
     *     @OA\RequestBody(
     *         description="new answer",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(ref="#/components/schemas/Instructor_ExamAnswerResource_ScenarioCreate"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="new answer created",
     *        @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Instructor_ExamAnswerResource_Read")),
     *     ),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=403, ref="#/components/responses/403"),
     *    @OA\Response(response=409, ref="#/components/responses/409"),
     *    @OA\Response(response=422, ref="#/components/responses/422"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * ),
     */
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
     * Update an answer
     * @param int $id
     * @return ExamAnswerResource|array
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     * @throws ServerErrorHttpException
     * @throws ConflictHttpException
     *
     *
     * @OA\Put(
     *     path="/instructor/exam-answers/{id}",
     *     operationId="instructor::ExamAnswersController::actionUpdate",
     *     tags={"Instructor Exam Answers"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *        name="id",
     *        in="path",
     *        required=true,
     *        description="ID of the answer",
     *        @OA\Schema(ref="#/components/schemas/int_id"),
     *     ),
     *     @OA\Parameter(ref="#/components/parameters/yii2_fields"),
     *     @OA\Parameter(ref="#/components/parameters/yii2_expand"),
     *     @OA\RequestBody(
     *         description="updated answer",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(ref="#/components/schemas/Instructor_ExamAnswerResource_ScenarioUpdate"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="answer updated",
     *         @OA\JsonContent(ref="#/components/schemas/Instructor_ExamAnswerResource_Read"),
     *     ),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=403, ref="#/components/responses/403"),
     *    @OA\Response(response=404, ref="#/components/responses/404"),
     *    @OA\Response(response=409, ref="#/components/responses/409"),
     *    @OA\Response(response=422, ref="#/components/responses/422"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * ),
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
     * Delete an answer
     * @param int $id
     * @return void|BadRequestHttpException
     * @throws ConflictHttpException
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     * @throws ServerErrorHttpException
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     *
     * @OA\Delete(
     *     path="/instructor/exam-answers/{id}",
     *     operationId="instructor::ExamAnswersController::actionDelete",
     *     tags={"Instructor Exam Answers"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *        name="id",
     *        in="path",
     *        required=true,
     *        description="ID of the answer",
     *        @OA\Schema(ref="#/components/schemas/int_id"),
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="answer deleted",
     *     ),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=403, ref="#/components/responses/403"),
     *    @OA\Response(response=404, ref="#/components/responses/404"),
     *    @OA\Response(response=409, ref="#/components/responses/409"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * )
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
