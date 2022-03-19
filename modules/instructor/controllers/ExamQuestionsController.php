<?php

namespace app\modules\instructor\controllers;

use app\models\ExamTestInstanceQuestion;
use app\modules\instructor\resources\ExamQuestionResource;
use app\modules\instructor\resources\ExamQuestionSetResource;
use app\modules\instructor\resources\ExamTestInstanceResource;
use app\modules\instructor\resources\ExamTestResource;
use app\resources\SemesterResource;
use Throwable;
use Yii;
use yii\data\ActiveDataProvider;
use yii\db\StaleObjectException;
use yii\helpers\ArrayHelper;
use yii\web\BadRequestHttpException;
use yii\web\ConflictHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;

/**
 * This class provides access to exam questions for instructors
 */
class ExamQuestionsController extends BaseInstructorRestController
{
    protected function verbs()
    {
        return ArrayHelper::merge(
            parent::verbs(),
            [
                'list-for-set' => ['GET'],
                'list-for-test' => ['GET'],
                'create' => ['POST'],
                'delete' => ['DELETE'],
                'update' => ['PATCH', 'PUT'],
            ]
        );
    }

    /**
     * Get questions from a question set
     * @param int $questionsetID
     * @return ActiveDataProvider
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     *
     * @OA\Get(
     *     path="/instructor/exam-questions/list-for-set",
     *     operationId="instructor::ExamQuestonsController::actionListForSet",
     *     tags={"Instructor Exam Questions"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="questionsetID",
     *         in="query",
     *         required=true,
     *         description="ID of the question set",
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
     *        @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Instructor_ExamQuestionResource_Read")),
     *    ),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=403, ref="#/components/responses/403"),
     *    @OA\Response(response=404, ref="#/components/responses/404"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * ),
     */
    public function actionListForSet($questionsetID)
    {
        $set = ExamQuestionSetResource::findOne($questionsetID);

        if (is_null($set)) {
            throw new NotFoundHttpException(Yii::t('app', 'Question set does not exist'));
        }

        // Check permissions for the original object
        if (!Yii::$app->user->can(
            'manageGroup',
            [
                'courseID' => $set->courseID,
                'semesterID' => SemesterResource::getActualID()
            ]
        )
        ) {
            throw new ForbiddenHttpException(
                Yii::t('app', 'You must be an incumbent instructor of the course to perform this action!')
            );
        }

        return new ActiveDataProvider(
            [
                'query' => $set->getQuestions(),
                'sort' => false,
                'pagination' => false,
            ]
        );
    }

    /**
     * Get questions for a test.
     * List questions for the given test (and for a user, if the test instances are unique for the given test).
     * @param int $testID
     * @param int|null $userID required when the test instances are unique
     * @return ActiveDataProvider|array
     * @throws BadRequestHttpException
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     *
     * @OA\Get(
     *     path="/instructor/exam-questions/list-for-test",
     *     operationId="instructor::ExamQuestonsController::actionListForTest",
     *     tags={"Instructor Exam Questions"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="testID",
     *         in="query",
     *         required=true,
     *         description="ID of the test",
     *         explode=true,
     *         @OA\Schema(ref="#/components/schemas/int_id")
     *     ),
     *     @OA\Parameter(
     *         name="userID",
     *         in="query",
     *         required=false,
     *         description="ID of the user (required if the test instances are unique)",
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
     *        @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Instructor_ExamQuestionResource_Read")),
     *    ),
     *    @OA\Response(response=400, ref="#/components/responses/400"),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=403, ref="#/components/responses/403"),
     *    @OA\Response(response=404, ref="#/components/responses/404"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * ),
     */
    public function actionListForTest($testID, $userID = null)
    {
        $test = ExamTestResource::findOne($testID);

        if (is_null($test)) {
            throw new NotFoundHttpException(Yii::t('app', 'Test does not found'));
        }

        if (!Yii::$app->user->can('manageGroup', ['groupID' => $test->groupID])) {
            throw new ForbiddenHttpException(
                Yii::t('app', 'You must be an instructor of the group to perform this action!')
            );
        }

        // Find one testinstance for the given test
        $testinstanceQuery = ExamTestInstanceResource::find()
            ->where(["testID" => $testID]);

        // If test instances are unique, then the userID is required
        if ($test->unique) {
            if (is_null($userID)) {
                throw new BadRequestHttpException(
                    Yii::t('app', 'Test instances are unique: you must provide an userID')
                );
            }

            // find test instance for the given user
            $testinstanceQuery->andWhere(['userID' => $userID]);
        }

        $testinstance = $testinstanceQuery->one();

        if (is_null($testinstance)) {
            return [];
        }

        return new ActiveDataProvider(
            [
                'query' => $testinstance->getQuestions(),
                'pagination' => false
            ]
        );
    }

    /**
     * Create a question
     * @return ExamQuestionResource|array
     * @throws ForbiddenHttpException
     * @throws ServerErrorHttpException
     *
     * @OA\Post(
     *     path="/instructor/exam-questions",
     *     operationId="instructor::ExamQuestonsController::actionCreate",
     *     tags={"Instructor Exam Questions"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(ref="#/components/parameters/yii2_fields"),
     *     @OA\Parameter(ref="#/components/parameters/yii2_expand"),
     *     @OA\RequestBody(
     *         description="new question",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(ref="#/components/schemas/Instructor_ExamQuestionResource_ScenarioCreate"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="new question created",
     *        @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Instructor_ExamQuestionResource_Read")),
     *     ),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=403, ref="#/components/responses/403"),
     *    @OA\Response(response=422, ref="#/components/responses/422"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * ),
     */
    public function actionCreate()
    {
        $question = new ExamQuestionResource();
        $question->scenario = ExamQuestionResource::SCENARIO_CREATE;
        $question->load(Yii::$app->request->post(), '');

        if (!$question->validate()) {
            $this->response->statusCode = 422;
            return $question->errors;
        }

        $set = ExamQuestionSetResource::findOne($question->questionsetID);

        if (!Yii::$app->user->can(
            'manageGroup',
            [
                'courseID' => $set->courseID,
                'semesterID' => SemesterResource::getActualID()
            ]
        )
        ) {
            throw new ForbiddenHttpException(
                Yii::t('app', 'You must be an incumbent instructor of the course to perform this action!')
            );
        }

        if ($question->save(false)) {
            $this->response->statusCode = 201;
            return $question;
        } else {
            throw new ServerErrorHttpException(Yii::t('app', 'A database error occurred'));
        }
    }

    /**
     * Update question
     * @param int $id
     * @return ExamQuestionResource|array
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     * @throws ServerErrorHttpException
     * @throws ConflictHttpException
     *
     * @OA\Put(
     *     path="/instructor/exam-questions/{id}",
     *     operationId="instructor::ExamQuestonsController::actionUpdate",
     *     tags={"Instructor Exam Questions"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *        name="id",
     *        in="path",
     *        required=true,
     *        description="ID of the question",
     *        @OA\Schema(ref="#/components/schemas/int_id"),
     *     ),
     *     @OA\Parameter(ref="#/components/parameters/yii2_fields"),
     *     @OA\Parameter(ref="#/components/parameters/yii2_expand"),
     *     @OA\RequestBody(
     *         description="updated question",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(ref="#/components/schemas/Instructor_ExamQuestionResource_ScenarioUpdate"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="question updated",
     *         @OA\JsonContent(ref="#/components/schemas/Instructor_ExamQuestionResource_Read"),
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
        $question = ExamQuestionResource::findOne($id);

        if (is_null($question)) {
            throw new NotFoundHttpException(Yii::t('app', 'Question does not exist'));
        }

        $question->scenario = ExamQuestionResource::SCENARIO_UPDATE;
        $question->load(Yii::$app->request->post(), '');

        if (!$question->validate()) {
            $this->response->statusCode = 422;
            return $question->errors;
        }

        $set = ExamQuestionSetResource::findOne($question->questionsetID);

        if (!Yii::$app->user->can(
            'manageGroup',
            [
                'courseID' => $set->courseID,
                'semesterID' => SemesterResource::getActualID()
            ]
        )
        ) {
            throw new ForbiddenHttpException(
                Yii::t('app', 'You must be an incumbent instructor of the course to perform this action!')
            );
        }

        if (ExamTestInstanceQuestion::findOne(["questionID" => $id]) != null) {
            throw new ConflictHttpException(
                Yii::t('app', 'Cannot update question because a test that contains it was finalized')
            );
        }

        if ($question->save(false)) {
            return $question;
        } else {
            throw new ServerErrorHttpException(Yii::t('app', 'A database error occurred'));
        }
    }

    /**
     * Delete a question
     * @param int $id
     * @return void
     * @throws ConflictHttpException
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     * @throws ServerErrorHttpException
     * @throws Throwable
     * @throws StaleObjectException
     *
     * @OA\Delete(
     *     path="/instructor/exam-questions/{id}",
     *     operationId="instructor::ExamQuestonsController::actionDelete",
     *     tags={"Instructor Exam Questions"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *        name="id",
     *        in="path",
     *        required=true,
     *        description="ID of the question",
     *        @OA\Schema(ref="#/components/schemas/int_id"),
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="question deleted",
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
        $question = ExamQuestionResource::findOne($id);
        if (is_null($question)) {
            throw new NotFoundHttpException(Yii::t('app', 'Question does not exist'));
        }

        if (!Yii::$app->user->can(
            'manageGroup',
            [
                'courseID' => $question->questionSet->courseID,
                'semesterID' => SemesterResource::getActualID()
            ]
        )
        ) {
            throw new ForbiddenHttpException(
                Yii::t('app', 'You must be an incumbent instructor of the course to perform this action!')
            );
        }

        if (ExamTestInstanceQuestion::findOne(["questionID" => $id]) != null) {
            throw new ConflictHttpException(
                Yii::t('app', 'Cannot update question because a test that contains it was finalized')
            );
        }

        try {
            $question->delete();
            $this->response->statusCode = 204;
            return;
        } catch (yii\db\IntegrityException $e) {
            throw new ConflictHttpException(
                Yii::t('app', 'Cannot delete question because it appears in a test instance')
            );
        } catch (yii\base\ErrorException $e) {
            throw new ServerErrorHttpException(Yii::t('app', 'A database error occurred'));
        }
    }
}
