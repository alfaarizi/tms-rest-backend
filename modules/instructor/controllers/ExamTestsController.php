<?php

namespace app\modules\instructor\controllers;

use app\models\Group;
use app\models\Subscription;
use app\models\User;
use app\models\ExamQuestion;
use app\models\ExamTest;
use app\models\ExamTestInstance;
use app\models\ExamTestInstanceQuestion;
use app\modules\instructor\resources\ExamQuestionSetResource;
use app\modules\instructor\resources\ExamTestInstanceResource;
use app\modules\instructor\resources\ExamTestResource;
use app\modules\instructor\resources\GroupResource;
use app\resources\SemesterResource;
use Yii;
use yii\data\ActiveDataProvider;
use yii\db\ActiveQuery;
use yii\helpers\ArrayHelper;
use yii\web\BadRequestHttpException;
use yii\web\ConflictHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;

/**
 * This class provides access to exam tests for instructors
 */
class ExamTestsController extends BaseInstructorRestController
{
    protected function verbs()
    {
        return ArrayHelper::merge(
            parent::verbs(),
            [
                'index' => ['GET'],
                'view' => ['GET'],
                'create' => ['POST'],
                'delete' => ['DELETE'],
                'update' => ['PATCH', 'PUT'],
                'duplicate' => ['POST'],
                'finalize' => ['POST']
            ]
        );
    }

    public function actionIndex($semesterID)
    {
        // Groups the current user lectures or instructs in this semester.
        $userGroups = GroupResource::find()
            ->instructorAccessibleGroups(Yii::$app->user->id, $semesterID)
            ->select('g.id');

        return new ActiveDataProvider(
            [
                'query' => ExamTestResource::find()->forGroups($userGroups)->orderBy('id')->indexBy('id'),
                'sort' => false,
                'pagination' => false,
            ]
        );
    }

    /**
     * @param int $id
     * @return ExamTestResource
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     */
    public function actionView($id)
    {
        $test = ExamTestResource::findOne($id);

        if (is_null($test)) {
            throw new NotFoundHttpException(Yii::t('app', 'Test does not exist'));
        }

        if (!Yii::$app->user->can('manageGroup', ['groupID' => $test->groupID])) {
            throw new ForbiddenHttpException(
                Yii::t('app', 'You must be an instructor of the group to perform this action!')
            );
        }

        return $test;
    }

    /**
     * @return ExamTestResource|array
     * @throws ForbiddenHttpException
     * @throws ServerErrorHttpException
     */
    public function actionCreate()
    {
        $test = new ExamTestResource();
        $test->scenario = ExamTestResource::SCENARIO_CREATE;
        $test->load(Yii::$app->request->post(), '');

        if (!$test->validate()) {
            $this->response->statusCode = 422;
            return $test->errors;
        }

        $questionSet = ExamQuestionSetResource::findone($test->questionsetID);

        if (!Yii::$app->user->can(
            'manageGroup',
            [
                'courseID' => $questionSet->courseID,
                'semesterID' => SemesterResource::getActualID()
            ]
        )
        ) {
            throw new ForbiddenHttpException(
                Yii::t('app', 'You must be an incumbent instructor of the course to perform this action!')
            );
        }

        if (!Yii::$app->user->can('manageGroup', ['groupID' => $test->groupID])) {
            throw new ForbiddenHttpException(
                Yii::t('app', 'You must be an instructor of the group to perform this action!')
            );
        }

        if ($test->save(false)) {
            $this->response->statusCode = 201;
            return $test;
        } else {
            throw new ServerErrorHttpException(Yii::t('app', 'A database error occurred'));
        }
    }

    /**
     * @param int $id
     * @return ExamTestResource|array
     * @throws ConflictHttpException
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     * @throws ServerErrorHttpException
     */
    public function actionUpdate($id)
    {
        $test = ExamTestResource::findOne($id);

        if (is_null($test)) {
            throw new NotFoundHttpException(Yii::t('app', 'Test does not exist'));
        }

        if (!Yii::$app->user->can('manageGroup', ['groupID' => $test->groupID])) {
            throw new ForbiddenHttpException(
                Yii::t('app', 'You must be an instructor of the group to perform this action!')
            );
        }

        if (!is_null(ExamTestInstanceResource::findOne(["testID" => $id]))) {
            throw new ConflictHttpException(Yii::t('app', 'Cannot update test after finalizing'));
        }

        $test->scenario = ExamTestResource::SCENARIO_UPDATE;
        $test->load(Yii::$app->request->post(), '');

        if ($test->save()) {
            return $test;
        } elseif ($test->hasErrors()) {
            $this->response->statusCode = 422;
            return $test->errors;
        } else {
            throw new ServerErrorHttpException(Yii::t('app', 'A database error occurred'));
        }
    }

    /**
     * @param int $id
     * @throws ConflictHttpException
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     * @throws ServerErrorHttpException
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public function actionDelete($id)
    {
        $test = ExamTestResource::findOne($id);

        if (is_null($test)) {
            throw new NotFoundHttpException(Yii::t('app', 'Test does not exist'));
        }

        if (!Yii::$app->user->can('manageGroup', ['groupID' => $test->groupID])) {
            throw new ForbiddenHttpException(
                Yii::t('app', 'You must be an instructor of the group to perform this action!')
            );
        }

        try {
            $test->delete();
            $this->response->statusCode = 204;
            return;
        } catch (yii\db\IntegrityException $e) {
            throw new ConflictHttpException(Yii::t('app', 'Cannot delete test because it is already in progress'));
        } catch (yii\base\ErrorException $e) {
            throw new ServerErrorHttpException(Yii::t('app', 'A database error occurred'));
        }
    }

    /**
     * @param int $id
     * @return ExamTestResource|array
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     * @throws ServerErrorHttpException
     */
    public function actionDuplicate($id)
    {
        $test = ExamTestResource::findOne($id);

        if (is_null($test)) {
            throw new NotFoundHttpException(Yii::t('app', 'Test does not exist'));
        }

        if (!Yii::$app->user->can('manageGroup', ['groupID' => $test->groupID])) {
            throw new ForbiddenHttpException(
                Yii::t('app', 'You must be an instructor of the group to perform this action!')
            );
        }

        $copy = new ExamTestResource();
        $copy->scenario = ExamTestResource::SCENARIO_CREATE;
        $copy->availablefrom = $test->availablefrom;
        //End of availability is set to next day if the original one is a past date
        $copy->availableuntil = $test->availableuntil > date('Y-m-d H:i:s')
            ? $test->availableuntil : date('Y-m-d H:i:s', strtotime('+1 day'));
        $copy->shuffled = $test->shuffled;
        $copy->unique = $test->unique;
        $copy->duration = $test->duration;
        $copy->name = $test->name . ' ' . Yii::t('app', '(copy)');
        $copy->questionamount = $test->questionamount;
        $copy->questionsetID = $test->questionsetID;
        $copy->groupID = $test->groupID;

        if ($copy->save()) {
            $this->response->statusCode = 201;
            return $copy;
        } elseif ($copy->hasErrors()) {
            $this->response->statusCode = 422;
            return $copy->errors;
        } else {
            throw new ServerErrorHttpException(Yii::t('app', 'A database error occurred'));
        }
    }


    /**
     * Finalizes test. The test cannot be updated after that.
     * Generates test instances of the given test for the group the test is bound to
     * The questions will be bound to the test instances via a junction table
     * @param int $id is the id of the test used for generating test instances
     */
    public function actionFinalize($id)
    {
        $test = ExamTest::findOne($id);

        if (is_null($test)) {
            throw new NotFoundHttpException(Yii::t('app', 'Test does not exist'));
        }

        if (!Yii::$app->user->can('manageGroup', ['groupID' => $test->groupID])) {
            throw new ForbiddenHttpException(
                Yii::t('app', 'You must be an instructor of the group to perform this action!')
            );
        }

        if ($test->questionSet->getQuestions()->count() < $test->questionamount) {
            throw new BadRequestHttpException(
                Yii::t('app', 'This question set doesn\'t have enough questions')
            );
        }

        if (!is_null(ExamTestInstance::findOne(["testID" => $id]))) {
            throw new BadRequestHttpException(Yii::t('app', 'Test was already finalized'));
        }

        $groupID = $test->groupID;
        $group = Group::findOne($groupID);
        $subscriptions = Subscription::find()->select('userID')->where(
            [
                'groupID' => $groupID,
                'semesterID' => $group->semesterID,
                'isAccepted' => true
            ]
        );
        $count = User::find()->where(['in', 'id', $subscriptions])->count();

        if ($count < 1) {
            throw new BadRequestHttpException(Yii::t('app', 'The selected group is empty. Please add at least one student!'));
        }


        $users = User::find()->where(['in', 'id', $subscriptions])->all();

        $batchTests = array();
        foreach ($users as $user) {
            $testInstance = new ExamTestInstance();
            $testInstance->score = 0;
            $testInstance->submitted = 0;
            $testInstance->userID = $user->id;
            $testInstance->testID = $id;
            $batchTests[] = $testInstance->attributes;
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $testAttr = ['id', 'starttime', 'finishtime', 'submitted', 'score', 'userID', 'testID'];
            Yii::$app->db->createCommand()->batchInsert(ExamTestInstance::tableName(), $testAttr, $batchTests)->execute();

            //Shuffle array of questions and slice the first n where n is the question amount
            $questions = ExamQuestion::find()->where(['questionsetID' => $test->questionsetID])->all();
            shuffle($questions);
            $chosen = array_slice($questions, 0, $test->questionamount);
            $batchQuestions = array();
            $questionAttr = ['questionID', 'testinstanceID'];
            foreach (ExamTestInstance::findAll(['testID' => $test->id]) as $testInstance) {
                //In case of unique test instances questions are being shuffled for every user
                if ($test->unique) {
                    shuffle($questions);
                    $chosen = array_slice($questions, 0, $test->questionamount);
                    foreach ($chosen as $question) {
                        $batchQuestions[] = [$question->id, $testInstance->id];
                    }
                } else {
                    foreach ($chosen as $question) {
                        $batchQuestions[] = [$question->id, $testInstance->id];
                    }
                }
            }
            Yii::$app->db->createCommand()->batchInsert(
                ExamTestInstanceQuestion::tableName(),
                $questionAttr,
                $batchQuestions
            )->execute();

            $transaction->commit();
            $this->response->statusCode = 204;
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw new ServerErrorHttpException(Yii::t('app', 'A database error occurred'));
        }
    }
}
