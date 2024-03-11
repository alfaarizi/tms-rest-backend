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
use yii\helpers\ArrayHelper;
use yii\web\BadRequestHttpException;
use yii\web\ConflictHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;

/**
 * @OA\PathItem(
 *   path="/instructor/exam-tests/{id}",
 *   @OA\Parameter(
 *      name="id",
 *      in="path",
 *      required=true,
 *      description="ID of the test",
 *      @OA\Schema(ref="#/components/schemas/int_id")
 *   ),
 * )
 */

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

    /**
     * List tests from the given semester
     *
     * @OA\Get(
     *     path="/instructor/exam-tests",
     *     operationId="instructor::ExamTestsController::actionIndex",
     *     tags={"Instructor Exam Tests"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *        name="semesterID",
     *        in="query",
     *        required=true,
     *        description="ID of the semester",
     *        @OA\Schema(ref="#/components/schemas/int_id"),
     *     ),
     *     @OA\Parameter(ref="#/components/parameters/yii2_fields"),
     *     @OA\Parameter(ref="#/components/parameters/yii2_expand"),
     *     @OA\Parameter(ref="#/components/parameters/yii2_sort"),
     *
     *     @OA\Response(
     *         response=200,
     *         description="successful operation",
     *         @OA\JsonContent(ref="#/components/schemas/Instructor_ExamTestResource_Read"),
     *     ),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * ),
     */
    public function actionIndex(int $semesterID): ActiveDataProvider
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
     * View a test
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     *
     * @OA\Get(
     *     path="/instructor/exam-tests/{id}",
     *     operationId="instructor::ExamTestsController::actionView",
     *     tags={"Instructor Exam Tests"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(ref="#/components/parameters/yii2_fields"),
     *     @OA\Parameter(ref="#/components/parameters/yii2_expand"),
     *
     *     @OA\Response(
     *         response=200,
     *         description="successful operation",
     *         @OA\JsonContent(ref="#/components/schemas/Instructor_ExamTestResource_Read"),
     *     ),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=403, ref="#/components/responses/403"),
     *    @OA\Response(response=404, ref="#/components/responses/404"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * ),
     */
    public function actionView(int $id): ExamTestResource
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
     * Create a new test
     * @return ExamTestResource|array
     * @throws ForbiddenHttpException
     * @throws ServerErrorHttpException
     *
     * @OA\Post(
     *     path="/instructor/exam-tests",
     *     tags={"Instructor Exam Tests"},
     *     operationId="instructor::ExamTestsController::actionCreate",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(ref="#/components/parameters/yii2_fields"),
     *     @OA\Parameter(ref="#/components/parameters/yii2_expand"),
     *     @OA\RequestBody(
     *         description="new test",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(ref="#/components/schemas/Instructor_ExamTestResource_ScenarioCreate"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="new test created",
     *         @OA\JsonContent(ref="#/components/schemas/Instructor_ExamTestResource_Read"),
     *     ),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=403, ref="#/components/responses/403"),
     *    @OA\Response(response=422, ref="#/components/responses/422"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * )
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
            Yii::info(
                "A new test has been created: $test->name ($test->id)." . PHP_EOL .
                "Course: {$test->group->course->name}" . PHP_EOL .
                "Group number: {$test->group->number}, groupID: {$test->groupID}",
                __METHOD__
            );
            $this->response->statusCode = 201;
            return $test;
        } else {
            throw new ServerErrorHttpException(Yii::t('app', 'A database error occurred'));
        }
    }

    /**
     * Update a test
     * @return ExamTestResource|array
     * @throws ConflictHttpException
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     * @throws ServerErrorHttpException
     *
     * @OA\Put(
     *     path="/instructor/exam-tests/{id}",
     *     operationId="instructor::ExamTestsController::actionUpdate",
     *     tags={"Instructor Exam Tests"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(ref="#/components/parameters/yii2_fields"),
     *     @OA\Parameter(ref="#/components/parameters/yii2_expand"),
     *     @OA\RequestBody(
     *         description="updated test",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(ref="#/components/schemas/Instructor_ExamTestResource_ScenarioUpdate"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="test updated",
     *         @OA\JsonContent(ref="#/components/schemas/Instructor_ExamTestResource_Read"),
     *     ),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=403, ref="#/components/responses/403"),
     *    @OA\Response(response=404, ref="#/components/responses/404"),
     *    @OA\Response(response=409, ref="#/components/responses/409"),
     *    @OA\Response(response=422, ref="#/components/responses/422"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * )
     */
    public function actionUpdate(int $id)
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
     * Create a new test
     * @throws ConflictHttpException
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     * @throws ServerErrorHttpException
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     *
     * @OA\Delete(
     *     path="/instructor/exam-tests/{id}",
     *     operationId="instructor::ExamTestsController::actionDelete",
     *     tags={"Instructor Exam Tests"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=204,
     *         description="test deleted",
     *     ),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=403, ref="#/components/responses/403"),
     *    @OA\Response(response=404, ref="#/components/responses/404"),
     *    @OA\Response(response=409, ref="#/components/responses/409"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * )
     */
    public function actionDelete(int $id): void
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
        } catch (\yii\db\IntegrityException $e) {
            throw new ConflictHttpException(Yii::t('app', 'Cannot delete test because it is already in progress'));
        } catch (\yii\base\ErrorException $e) {
            throw new ServerErrorHttpException(Yii::t('app', 'A database error occurred'));
        }
    }

    /**
     * Copy a test
     * @return ExamTestResource|array
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     * @throws ServerErrorHttpException
     *
     * @OA\Post(
     *     path="/instructor/exam-tests/{id}/duplicate",
     *     tags={"Instructor Exam Tests"},
     *     operationId="instructor::ExamTestsController::actionDuplicate",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *        name="id",
     *        in="path",
     *        required=true,
     *        description="ID of the test",
     *        @OA\Schema(ref="#/components/schemas/int_id")
     *     ),
     *     @OA\Parameter(ref="#/components/parameters/yii2_fields"),
     *     @OA\Parameter(ref="#/components/parameters/yii2_expand"),
     *     @OA\Response(
     *         response=200,
     *         description="question set duplicated",
     *         @OA\JsonContent(ref="#/components/schemas/Instructor_ExamTestResource_Read"),
     *     ),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=403, ref="#/components/responses/403"),
     *    @OA\Response(response=404, ref="#/components/responses/404"),
     *    @OA\Response(response=422, ref="#/components/responses/422"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * )
     */
    public function actionDuplicate(int $id)
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
        $copy->availableuntil = strtotime($test->availableuntil) > time()
            ? $test->availableuntil : date('Y-m-d H:i:s', strtotime('+1 day'));
        $copy->shuffled = $test->shuffled;
        $copy->unique = $test->unique;
        $copy->duration = $test->duration;
        $copy->name = $test->name . ' ' . Yii::t('app', '(copy)');
        $copy->questionamount = $test->questionamount;
        $copy->questionsetID = $test->questionsetID;
        $copy->groupID = $test->groupID;

        if ($copy->save()) {
            Yii::info(
                "A test has been copied, new test: $copy->name ($copy->id)." . PHP_EOL .
                "Course: {$test->group->course->name}" . PHP_EOL .
                "Group number: {$test->group->number}, groupID: {$test->groupID}",
                __METHOD__
            );
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
     * Finalize test.
     * The test cannot be updated after that.
     * Generates test instances of the given test for the group.
     * The questions will be bound to the test instances via a junction table.
     * @param int $id is the id of the test used for generating test instances
     *
     * @throws BadRequestHttpException
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     * @throws ServerErrorHttpException
     * @OA\Post(
     *     path="/instructor/exam-tests/{id}/finalize",
     *     tags={"Instructor Exam Tests"},
     *     operationId="instructor::ExamTestsController::actionFinalize",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *        name="id",
     *        in="path",
     *        required=true,
     *        description="ID of the test",
     *        @OA\Schema(ref="#/components/schemas/int_id")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="test finalized",
     *     ),
     *    @OA\Response(response=400, ref="#/components/responses/400"),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=403, ref="#/components/responses/403"),
     *    @OA\Response(response=404, ref="#/components/responses/404"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * )
     */
    public function actionFinalize(int $id): void
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

        try {
            $test->finalize();
            $this->response->statusCode = 204;
        } catch (\LengthException $e) {
            throw new BadRequestHttpException($e->getMessage());
        } catch (\Exception $e) {
            throw new ServerErrorHttpException(Yii::t('app', 'A database error occurred'));
        }
    }
}
