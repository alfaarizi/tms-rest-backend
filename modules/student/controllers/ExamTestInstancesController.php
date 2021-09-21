<?php

namespace app\modules\student\controllers;

use app\models\ExamAnswer;
use app\models\ExamSubmittedAnswer;
use app\models\ExamTest;
use app\models\ExamTestInstance;
use app\modules\student\resources\ExamResultQuestionResource;
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
 * This class provides access to test instances for students
 */
class ExamTestInstancesController extends BaseStudentRestController
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
     * @param int $semesterID
     * @param int $submitted
     * @return ActiveDataProvider
     */
    public function actionIndex($semesterID, $submitted)
    {
        $submitted = filter_var($submitted, FILTER_VALIDATE_BOOLEAN);
        // All tests for the given semester
        $tests = ExamTest::find()
            ->select('id')
            ->forSemester($semesterID);

        // List active tests
        if (!$submitted) {
            $tests = $tests->onlyActive();
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
     * @param int $id
     * @return ExamTestInstanceResource
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     */
    public function actionView($id)
    {
        $testInstance = ExamTestInstanceResource::findOne($id);

        if (is_null($testInstance)) {
            throw new NotFoundHttpException(Yii::t('app', "Test instance does not exist"));
        }

        if ($testInstance->userID != Yii::$app->user->id) {
            throw new ForbiddenHttpException(Yii::t('app', "You don't have permission to access this test instance"));
        }

        if (!$testInstance->submitted &&
            ($testInstance->test->availablefrom > date('Y-m-d H:i:s') || $testInstance->test->availableuntil < date('Y-m-d H:i:s'))
        ) {
            throw new ForbiddenHttpException(Yii::t('app', "You don't have permission to access this test instance"));
        }

        return $testInstance;
    }

    /**
     * @param int $id
     * @return ActiveDataProvider
     * @throws BadRequestHttpException
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     */
    public function actionResults($id)
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
     * @param int $id
     * @return ExamWriterResource
     * @throws BadRequestHttpException
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     */
    public function actionStartWrite($id)
    {
        $testInstance = ExamTestInstance::findOne($id);

        if (is_null($testInstance)) {
            throw new NotFoundHttpException(Yii::t('app', "Test instance does not exist"));
        }

        if ($testInstance->submitted || $testInstance->test->availablefrom > date('Y-m-d H:i:s')
            || $testInstance->test->availableuntil < date('Y-m-d H:i:s') || $testInstance->userID != Yii::$app->user->id) {

            throw new ForbiddenHttpException(Yii::t('app', "You don't have permission to modify this test instance"));
        }

        //Save starttime on the first occasion
        if (is_null($testInstance->starttime)) {
            $testInstance->starttime = date("Y-m-d H:i:s");
            $testInstance->save();
        }

        //Shuffle questions if necessary
        $questions = $testInstance->getQuestions()->all();
        if ($testInstance->test->shuffled) {
            shuffle($questions);
        }

        $duration = $this->calcDuration($testInstance);

        // Can't resume when test is over
        if ($duration <= 0) {
            throw new BadRequestHttpException(Yii::t('app', "Time is over: you can't resume this test"));
        }

        $questionResources = [];

        //Shuffle answers if necessary
        foreach ($questions as $question) {
            $questionResource = new ExamWriterQuestionResource($question->id, $question->text);
            $questionResources[] = $questionResource;

            //Shuffle answers if necessary
            if ($testInstance->test->shuffled) {
                $tmp = $question->getAnswers()->all();
                $questionResource->answers = array_map(function($i) {
                    return new ExamWriterAnswerResource($i->id, $i->text);
                }, $tmp);
            } else {
                $questionResource->answers = array_map(function($i) {
                    return new ExamWriterAnswerResource($i->id, $i->text);
                }, $question->getAnswers()->all());
            }
        }

        return new ExamWriterResource($testInstance->test->name, $duration / 1000, $questionResources);
    }

    /**
     * @param int $id
     * @return ExamTestInstanceResource
     * @throws BadRequestHttpException
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     * @throws ServerErrorHttpException
     */
    public function actionFinishWrite($id)
    {
        $testInstance = ExamTestInstanceResource::findOne($id);

        if (is_null($testInstance)) {
            throw new NotFoundHttpException(Yii::t('app', "Test instance does not exist"));
        }

        if ($testInstance->submitted || $testInstance->test->availablefrom > date('Y-m-d H:i:s')
            || $testInstance->test->availableuntil < date('Y-m-d H:i:s') || $testInstance->userID != Yii::$app->user->id) {

            throw new ForbiddenHttpException(Yii::t('app', "You don't have permission to modify this test instance"));
        }

        $submittedAnswers = [];
        $count = $testInstance->getQuestions()->count();
        $duration = $this->calcDuration($testInstance);

        for ($i = 0; $i < $count; ++$i) {
            $submittedAnswer = new ExamSubmittedAnswer();
            $submittedAnswer->testinstanceID = $id;
            $submittedAnswers[] = $submittedAnswer;
        }

        // Close test with 0 score when test is over
        // 30 seconds gratis time, so JavaScript-based auto-submission at the end of the test is still valid
        if ($duration <= -30000) {
            Yii::info(
                "Test instance saved with 0 points after timeout (testinstance: $testInstance->id). Post Data: "
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
                "Failed to load post data (testinstance: $testInstance->id)." .
                " Post Data: " . VarDumper::dumpAsString(Yii::$app->request->post()),
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
                        "Failed to validate answer ($answer->answerID) and testinsance ($testInstance->id)." .
                        " Post Data: " . VarDumper::dumpAsString(Yii::$app->request->post()),
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
            foreach ($testInstance->getQuestions()->all() as $question) {
                if ($question->getCorrectAnswers()->count() == 0 &&
                    count(array_filter($submittedAnswers, function($answer) use ($question) {
                        if (is_null($answer->answerID) || $answer->answerID == "") {
                            return false;
                        }
                        return $answer->getAnswer()->one()->questionID == $question->id;
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
            return $testInstance;

        }
        catch (\Exception $e) {
            $transaction->rollBack();
            Yii::error(
                "Failed to save answers ($testInstance->id)." .
                " Message: " . $e->getMessage() .
                " Post Data: " . VarDumper::dumpAsString(Yii::$app->request->post()),
                __METHOD__
            );
            throw new ServerErrorHttpException(Yii::t("app", "A database error occurred"));
        }
    }

    private function calcDuration($testInstance) {
        // Time left is set to remaining duration unless the test becomes unavailable sooner
        $spent = (time() - strtotime($testInstance->starttime)) * 1000;
        $timeLeft = (strtotime($testInstance->test->availableuntil) - time()) * 1000;
        $duration = $testInstance->test->duration * 60000 - $spent;
        $duration = min($timeLeft, $duration);

        return $duration;
    }
}
