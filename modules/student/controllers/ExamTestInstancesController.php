<?php

namespace app\modules\student\controllers;

use app\models\ExamAnswer;
use app\models\ExamQuestion;
use app\models\ExamTest;
use app\models\ExamTestInstance;
use app\modules\student\resources\ExamResultQuestionResource;
use app\modules\student\resources\ExamSubmittedAnswerResource;
use app\modules\student\resources\ExamTestInstanceResource;
use app\modules\student\resources\ExamWriterAnswerResource;
use app\modules\student\resources\ExamWriterQuestionResource;
use app\modules\student\resources\ExamWriterResource;
use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\db\Exception;
use yii\helpers\ArrayHelper;
use yii\helpers\VarDumper;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;

/**
 * This class provides access to test instance actions for students
 */
class ExamTestInstancesController extends BaseSubmissionsController
{
    protected function verbs()
    {
        return ArrayHelper::merge(
            parent::verbs(),
            [
                'index' => ['GET'],
                'view' => ['GET'],
                'results' => ['GET'],
                'start-write' => ['POST'],
                'finish-write' => ['POST'],
            ]
        );
    }

    /**
     * Get the list of the test instances
     * @throws BadRequestHttpException
     *
     * @OA\Get(
     *     path="/student/exam-test-instances",
     *     operationId="student::ExamTestInstancesController::actionIndex",
     *     tags={"Student Exam Test Instances"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="semesterID",
     *         in="query",
     *         required=true,
     *         description="ID of the semester",
     *         explode=true,
     *         @OA\Schema(ref="#/components/schemas/int_id")
     *     ),
     *     @OA\Parameter(
     *         name="submitted",
     *         in="query",
     *         required=true,
     *         description="List submitted or list finished tests",
     *         explode=true,
     *         @OA\Schema(type="boolean")
     *     ),
     *     @OA\Parameter(
     *         name="future",
     *         in="query",
     *         required=false,
     *         description="List future tests",
     *         explode=true,
     *         @OA\Schema(type="boolean")
     *     ),
     *     @OA\Parameter(ref="#/components/parameters/yii2_fields"),
     *     @OA\Parameter(ref="#/components/parameters/yii2_expand"),
     *     @OA\Parameter(ref="#/components/parameters/yii2_sort"),
     *
     *     @OA\Response(
     *        response=200,
     *        description="successful operation",
     *        @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Student_ExamTestInstanceResource_Read")),
     *    ),
     *    @OA\Response(response=400, ref="#/components/responses/400"),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * )
     */
    public function actionIndex(int $semesterID, bool $submitted, bool $future = false): ActiveDataProvider
    {
        if ($submitted && $future) {
            throw new BadRequestHttpException(Yii::t('app', "Future tests can not be submitted"));
        }

        // All tests for the given semester
        $tests = ExamTest::find()
            ->select('id')
            ->forSemester($semesterID);

        // List active tests
        if (!$submitted && !$future) {
            $tests = $tests->onlyActive();
        }

        // List future tests
        if (!$submitted && $future) {
            $tests = $tests->onlyFuture();
        }

        // Query test instances
        $query = ExamTestInstanceResource::find()
            ->forTests($tests)
            ->onlySubmitted($submitted)
            ->forUser(Yii::$app->user->id);

        return new ActiveDataProvider(
            [
                'query' => $query,
                'sort' => false,
                'pagination' => false
            ]
        );
    }

    /**
     * Get a test instance
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     *
     * @OA\Get(
     *     path="/student/exam-test-instances/{id}",
     *     operationId="student::ExamTestInstancesController::actionView",
     *     tags={"Student Exam Test Instances"},
     *     security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *          name="id",
     *          in="path",
     *          required=true,
     *          description="ID of the test instance",
     *          @OA\Schema(ref="#/components/schemas/int_id")
     *     ),
     *     @OA\Parameter(ref="#/components/parameters/yii2_fields"),
     *     @OA\Parameter(ref="#/components/parameters/yii2_expand"),
     *     @OA\Response(
     *         response=200,
     *         description="successful operation",
     *         @OA\JsonContent(ref="#/components/schemas/Student_ExamTestInstanceResource_Read"),
     *     ),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=403, ref="#/components/responses/403"),
     *    @OA\Response(response=404, ref="#/components/responses/404"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * )
     */
    public function actionView(int $id): ExamTestInstanceResource
    {
        $testInstance = ExamTestInstanceResource::findOne($id);

        if (is_null($testInstance)) {
            throw new NotFoundHttpException(Yii::t('app', "Test instance does not exist"));
        }

        if ($testInstance->userID != Yii::$app->user->id) {
            throw new ForbiddenHttpException(Yii::t('app', "You don't have permission to access this test instance"));
        }

        if (!$testInstance->submitted &&
            (strtotime($testInstance->test->availablefrom) > time() || strtotime($testInstance->test->availableuntil) < time())
        ) {
            throw new ForbiddenHttpException(Yii::t('app', "You don't have permission to access this test instance"));
        }

        return $testInstance;
    }

    /**
     * List the questions with the results for a submitted test instance
     * @throws BadRequestHttpException
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     *
     * @OA\Get(
     *     path="/student/exam-test-instances/{id}/results",
     *     operationId="student::ExamTestInstancesController::actionResults",
     *     tags={"Student Exam Test Instances"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the test instance",
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
     *        @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Student_ExamResultQuestionResource_Read")),
     *    ),
     *    @OA\Response(response=400, ref="#/components/responses/400"),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=403, ref="#/components/responses/403"),
     *    @OA\Response(response=404, ref="#/components/responses/404"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * )
     */
    public function actionResults(int $id): ActiveDataProvider
    {
        $instance = ExamTestInstanceResource::findOne($id);

        if (is_null($instance)) {
            throw new NotFoundHttpException(Yii::t('app', "Test instance does not exist"));
        }

        if ($instance->userID !== Yii::$app->user->id) {
            throw new ForbiddenHttpException(Yii::t('app', "You don't have permission to access this test instance"));
        }

        if (!$instance->submitted) {
            throw new BadRequestHttpException(Yii::t('app', "Test instance is not submitted"));
        }

        return new ActiveDataProvider(
            [
                'query' => ExamResultQuestionResource::find()->where(['testinstanceID' => $id]),
                'pagination' => false
            ]
        );
    }

    /**
     * Start writing a test instance.
     * This actions sets the starting time and returns with the questions and possible answers.
     * @throws BadRequestHttpException
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     *
     * @OA\Post(
     *     path="/student/exam-test-instances/{id}/start-write",
     *     operationId="student::ExamTestInstancesController::actionStartWrite",
     *     tags={"Student Exam Test Instances"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the test instance",
     *         explode=true,
     *         @OA\Schema(ref="#/components/schemas/int_id")
     *     ),
     *     @OA\Parameter(ref="#/components/parameters/yii2_fields"),
     *     @OA\Parameter(ref="#/components/parameters/yii2_expand"),
     *     @OA\Response(
     *         response=200,
     *         description="test writing started",
     *         @OA\JsonContent(ref="#/components/schemas/Student_ExamWriterResource_Read"),
     *     ),
     *    @OA\Response(response=400, ref="#/components/responses/400"),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=403, ref="#/components/responses/403"),
     *    @OA\Response(response=404, ref="#/components/responses/404"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * )
     */
    public function actionStartWrite(int $id): ExamWriterResource
    {
        $testInstance = ExamTestInstance::findOne($id);

        if (is_null($testInstance)) {
            throw new NotFoundHttpException(Yii::t('app', "Test instance does not exist"));
        }

        if ($testInstance->submitted || strtotime($testInstance->test->availablefrom) > time()
            || strtotime($testInstance->test->availableuntil) < time() || $testInstance->userID != Yii::$app->user->id) {

            throw new ForbiddenHttpException(Yii::t('app', "You don't have permission to access this test instance"));
        }

        //Save starttime on the first occasion
        if (is_null($testInstance->starttime)) {
            $testInstance->starttime = date("Y-m-d H:i:s");
            $testInstance->save();
        }

        //Shuffle questions if necessary
        /** @var ExamQuestion[] $questions */
        $questions = $testInstance->getQuestions()->all();
        if ($testInstance->test->shuffled) {
            shuffle($questions);
        }

        $duration = $this->calcDuration($testInstance);

        // Can't resume when test is over
        if ($duration <= 0) {
            Yii::warning(
                "A user started to write a test instance (testInstance: $id), but the test was over.",
                __METHOD__
            );
            throw new BadRequestHttpException(Yii::t('app', "Time is over: you can't resume this test"));
        }

        $questionResources = [];

        //Shuffle answers if necessary
        foreach ($questions as $question) {
            $questionResource = new ExamWriterQuestionResource($question->id, $question->text);
            $questionResources[] = $questionResource;

            //Shuffle answers if necessary
            /** @var ExamAnswer[] $tmp */
            $tmp = $question->getAnswers()->all();
            if ($testInstance->test->shuffled) {
                shuffle($tmp);
            }
            $questionResource->answers = array_map(function ($i) {
                return new ExamWriterAnswerResource($i->id, $i->text);
            }, $tmp);
        }

        Yii::info(
            "A user started to write a test instance (testInstance: $id)",
            __METHOD__
        );

        return new ExamWriterResource($testInstance->test->name, $duration / 1000, $questionResources);
    }

    /**
     * Finish writing the test instance.
     * This actions saves the results for the current test and calculates the score.
     * @return ExamTestInstanceResource|array
     * @throws BadRequestHttpException
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     * @throws ServerErrorHttpException
     *
     * @OA\Post(
     *     path="/student/exam-test-instances/{id}/finish-write",
     *     operationId="student::ExamTestInstancesController::actionFinishWrite",
     *     tags={"Student Exam Test Instances"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the test instance",
     *         explode=true,
     *         @OA\Schema(ref="#/components/schemas/int_id")
     *     ),
     *     @OA\Parameter(ref="#/components/parameters/yii2_fields"),
     *     @OA\Parameter(ref="#/components/parameters/yii2_expand"),
     *     @OA\RequestBody(
     *         description="submitted answers",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                type="array",
     *                @OA\Items(ref="#/components/schemas/Student_ExamSubmittedAnswerResource_ScenarioDefault")
     *             ),
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="test submitted",
     *         @OA\JsonContent(ref="#/components/schemas/Student_ExamTestInstanceResource_Read"),
     *     ),
     *    @OA\Response(response=400, ref="#/components/responses/400"),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=403, ref="#/components/responses/403"),
     *    @OA\Response(response=404, ref="#/components/responses/404"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * )
     */
    public function actionFinishWrite(int $id)
    {
        $testInstance = ExamTestInstanceResource::findOne($id);

        if (is_null($testInstance)) {
            throw new NotFoundHttpException(Yii::t('app', "Test instance does not exist"));
        }

        if ($testInstance->submitted || strtotime($testInstance->test->availablefrom) > time()
            || strtotime($testInstance->test->availableuntil) + 30 < time() || $testInstance->userID != Yii::$app->user->id) {
            // 30 seconds gratis time, so JavaScript-based auto-submission at the end of the test is still valid
            throw new ForbiddenHttpException(Yii::t('app', "You don't have permission to access this test instance"));
        }

        $submittedAnswers = [];
        $count = $testInstance->getQuestions()->count();
        $duration = $this->calcDuration($testInstance);

        for ($i = 0; $i < $count; ++$i) {
            $submittedAnswer = new ExamSubmittedAnswerResource();
            $submittedAnswer->testinstanceID = $id;
            $submittedAnswers[] = $submittedAnswer;
        }

        // Close test with 0 score when test is over
        // 30 seconds gratis time, so JavaScript-based auto-submission at the end of the test is still valid
        if ($duration <= -30000) {
            Yii::info(
                "A test instance has been saved with 0 points after timeout" .
                "(testInstanceID: $testInstance->id)." . PHP_EOL ." Post Data: "
                . VarDumper::dumpAsString(Yii::$app->request->post()),
                __METHOD__
            );
            $transaction = Yii::$app->getDb()->beginTransaction();
            try {
                foreach ($submittedAnswers as $answer) {
                    $answer->save();
                }
                $testInstance->score = 0;
                $testInstance->finishtime = date("Y-m-d H:i:s");
                $testInstance->submitted = true;
                $testInstance->save();
                $transaction->commit();
                return $testInstance;
            } catch (\Exception $e) {
                $transaction->rollBack();
                throw new ServerErrorHttpException(Yii::t("app", "A database error occurred"));
            }
        }

        if (!Model::loadMultiple($submittedAnswers, Yii::$app->request->post(), '')) {
            Yii::error(
                "Failed to load post data (testInstanceID: $testInstance->id)" . PHP_EOL .
                "Post Data:" . VarDumper::dumpAsString(Yii::$app->request->post()),
                __METHOD__
            );
            throw new BadRequestHttpException(Yii::t("app", "Failed to load post data"));
        }

        $transaction = Yii::$app->getDb()->beginTransaction();
        try {
            $score = 0;
            foreach ($submittedAnswers as $answer) {
                if ($answer->save()) {
                    if ($answer->answerID != '' && ExamAnswer::findOne($answer->answerID)->correct) {
                        $score++;
                    }
                } elseif ($answer->hasErrors()) {
                    $transaction->rollBack();
                    Yii::error(
                        "Failed to validate answer." . PHP_EOL .
                        "(answerID: $answer->answerID, testInstanceID: $testInstance->id)" . PHP_EOL .
                        "Post Data:" . VarDumper::dumpAsString(Yii::$app->request->post()),
                        __METHOD__
                    );
                    $this->response->statusCode = 422;
                    return $answer->errors;
                } else {
                    throw new Exception("Failed to save submitted answer ($answer->answerID)");
                }
            }

            // Increase score for each question that didn't have a correct answer,
            // if the student did not answer it
            /** @var ExamQuestion[] $questions */
            $questions = $testInstance->getQuestions()->all();
            foreach ($questions as $question) {
                if ($question->getCorrectAnswers()->count() == 0 &&
                    count(array_filter($submittedAnswers, function($answer) use ($question) {
                        if (is_null($answer->answerID) || $answer->answerID == "") {
                            return false;
                        }
                        /** @var ExamAnswer $ans */
                        $ans = $answer->getAnswer()->one();
                        return $ans->questionID == $question->id;
                    })) == 0) {
                    $score++;
                }
            }

            $testInstance->score = $score;
            $testInstance->finishtime = date("Y-m-d H:i:s");
            $testInstance->submitted = true;

            if (!$testInstance->save()) {
                throw new Exception("Failed to save test instance (id: $testInstance->id)");
            }
            $transaction->commit();

            Yii::info(
                "A test instance has been saved successfully (testInstanceID: $testInstance->id).",
                __METHOD__
            );

            return $testInstance;

        }
        catch (\Exception $e) {
            $transaction->rollBack();
            Yii::error(
                "Failed to save answers ($testInstance->id)" . PHP_EOL .
                "Message: " . $e->getMessage() . PHP_EOL .
                "Post Data:" . VarDumper::dumpAsString(Yii::$app->request->post()),
                __METHOD__
            );
            throw new ServerErrorHttpException(Yii::t("app", "A database error occurred"));
        }
    }

    private function calcDuration(ExamTestInstance $testInstance): int {
        // Time left is set to remaining duration unless the test becomes unavailable sooner
        $spent = (time() - strtotime($testInstance->starttime)) * 1000;
        $timeLeft = (strtotime($testInstance->test->availableuntil) - time()) * 1000;
        $duration = $testInstance->test->duration * 60000 - $spent;
        $duration = min($timeLeft, $duration);

        return $duration;
    }
}
