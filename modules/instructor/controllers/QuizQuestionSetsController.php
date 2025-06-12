<?php

namespace app\modules\instructor\controllers;

use app\models\QuizAnswer;
use app\models\QuizQuestion;
use app\models\QuizTestInstanceQuestion;
use app\models\Semester;
use app\modules\instructor\resources\QuizImageUploadResultResource;
use app\modules\instructor\resources\QuizQuestionSetResource;
use app\modules\instructor\resources\UploadFailedResource;
use app\resources\QuizImageResource;
use app\resources\SemesterResource;
use Throwable;
use Yii;
use yii\base\Exception;
use yii\data\ActiveDataProvider;
use yii\db\StaleObjectException;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;
use yii\web\ConflictHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;
use yii\web\UploadedFile;

/**
 * @OA\PathItem(
 *   path="/instructor/quiz-question-sets/{id}",
 *   @OA\Parameter(
 *      name="id",
 *      in="path",
 *      required=true,
 *      description="ID of the question set",
 *      @OA\Schema(ref="#/components/schemas/int_id")
 *   ),
 * )
 */

/**
 * This class provides access to quiz questionsets for instructors
 */
class QuizQuestionSetsController extends BaseInstructorRestController
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
                'list-images' => ['GET'],
                'upload-images' => ['POST'],
                'remove-image' => ['DELETE']
            ]
        );
    }

    /**
     * Get all questions sets
     *
     * @OA\Get(
     *     path="/instructor/quiz-question-sets",
     *     tags={"Instructor Quiz Question Sets"},
     *     operationId="instructor::QuizQuestonSetsController::actionIndex",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(ref="#/components/parameters/yii2_fields"),
     *     @OA\Parameter(ref="#/components/parameters/yii2_expand"),
     *     @OA\Parameter(ref="#/components/parameters/yii2_sort"),
     *
     *     @OA\Response(
     *        response=200,
     *        description="successful operation",
     *        @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Instructor_QuizQuestionSetResource_Read")),
     *    ),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * ),
     */
    public function actionIndex(): ActiveDataProvider
    {
        // Question sets for owned courses
        $questionSetsByCourse = QuizQuestionSetResource::find()
            ->listForOwnedCourses(Yii::$app->user->id);

        // Question sets for owned groups (only for active semester!)
        $questionSetsByGroup = QuizQuestionSetResource::find()
            ->listForOwnedGroups(Yii::$app->user->id, SemesterResource::getActualID());

        return new ActiveDataProvider(
            [
                'query' => $questionSetsByCourse->union($questionSetsByGroup),
                'sort' => false,
                'pagination' => false,
            ]
        );
    }

    /**
     * Get a question set
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     *
     * @OA\Get(
     *     path="/instructor/quiz-question-sets/{id}",
     *     tags={"Instructor Quiz Question Sets"},
     *     operationId="instructor::QuizQuestonSetsController::actionView",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(ref="#/components/parameters/yii2_fields"),
     *     @OA\Parameter(ref="#/components/parameters/yii2_expand"),
     *     @OA\Response(
     *         response=200,
     *         description="successful operation",
     *         @OA\JsonContent(ref="#/components/schemas/Instructor_QuizQuestionSetResource_Read"),
     *     ),
     *    @OA\Response(response=400, ref="#/components/responses/400"),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=404, ref="#/components/responses/404"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * )
     */
    public function actionView(int $id): QuizQuestionSetResource
    {
        $questionSet = QuizQuestionSetResource::findOne($id);

        if (is_null($questionSet)) {
            throw new NotFoundHttpException(Yii::t('app', 'Question set does not exist'));
        }

        if (!Yii::$app->user->can('manageGroup', [
            'courseID' => $questionSet->courseID,
            'semesterID' => Semester::getActualID()
        ])
        ) {
            throw new ForbiddenHttpException(
                Yii::t('app', 'You must be an incumbent instructor of the course to perform this action!')
            );
        }

        return $questionSet;
    }

    /**
     * Create a question set
     * @return QuizQuestionSetResource|array
     * @throws ForbiddenHttpException
     * @throws ServerErrorHttpException
     *
     * @OA\Post(
     *     path="/instructor/quiz-question-sets",
     *     tags={"Instructor Quiz Question Sets"},
     *     operationId="instructor::QuizQuestonSetsController::actionCreate",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(ref="#/components/parameters/yii2_fields"),
     *     @OA\Parameter(ref="#/components/parameters/yii2_expand"),
     *     @OA\RequestBody(
     *         description="new question set",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(ref="#/components/schemas/Instructor_QuizQuestionSetResource_ScenarioCreate"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="new question set created",
     *         @OA\JsonContent(ref="#/components/schemas/Instructor_QuizQuestionSetResource_Read"),
     *     ),
     *    @OA\Response(response=400, ref="#/components/responses/400"),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=403, ref="#/components/responses/403"),
     *    @OA\Response(response=422, ref="#/components/responses/422"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * )
     */
    public function actionCreate()
    {
        $model = new QuizQuestionSetResource();
        $model->scenario = QuizQuestionSetResource::SCENARIO_CREATE;
        $model->load(Yii::$app->request->post(), '');

        if (!$model->validate()) {
            $this->response->statusCode = 422;
            return $model->errors;
        }

        if (!Yii::$app->user->can('manageGroup', [
            'courseID' => $model->courseID,
            'semesterID' => Semester::getActualID()
        ])
        ) {
            throw new ForbiddenHttpException(
                Yii::t('app', 'You must be an incumbent instructor of the course to perform this action!')
            );
        }

        if ($model->save(false)) {
            $this->response->statusCode = 201;
            return $model;
        } else {
            throw new ServerErrorHttpException(Yii::t('app', 'A database error occurred'));
        }
    }

    /**
     * Update a question set
     * @return QuizQuestionSetResource|array
     * @throws ConflictHttpException
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     * @throws ServerErrorHttpException
     *
     * @OA\Put(
     *     path="/instructor/quiz-question-sets/{id}",
     *     operationId="instructor::QuizQuestonSetsController::actionUpdate",
     *     tags={"Instructor Quiz Question Sets"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(ref="#/components/parameters/yii2_fields"),
     *     @OA\Parameter(ref="#/components/parameters/yii2_expand"),
     *     @OA\RequestBody(
     *         description="updated question set",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(ref="#/components/schemas/Instructor_QuizQuestionSetResource_ScenarioUpdate"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="question set updated",
     *         @OA\JsonContent(ref="#/components/schemas/Instructor_QuizQuestionSetResource_Read"),
     *     ),
     *    @OA\Response(response=400, ref="#/components/responses/400"),
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
        $questionSet = QuizQuestionSetResource::findOne($id);

        if (is_null($questionSet)) {
            throw new NotFoundHttpException(Yii::t('app', 'Question set does not exist'));
        }

        // Check permissions for the original object
        if (!Yii::$app->user->can('manageGroup', [
            'courseID' => $questionSet->courseID,
            'semesterID' => Semester::getActualID()
        ])
        ) {
            throw new ForbiddenHttpException(
                Yii::t('app', 'You must be an incumbent instructor of the course to perform this action!')
            );
        }

        if (!empty(
            QuizTestInstanceQuestion::find()
                ->where(["in", "questionID", $questionSet->getQuestions()->select("id")])
                ->all()
        )) {
            throw new ConflictHttpException(
                Yii::t('app', 'Cannot update question set because a related test was finalized')
            );
        }

        $questionSet->scenario = QuizQuestionSetResource::SCENARIO_UPDATE;
        $questionSet->load(Yii::$app->request->post(), '');

        if (!$questionSet->validate()) {
            $this->response->statusCode = 422;
            return $questionSet->errors;
        }

        // Check permissions for the modified object (courseID can be changed)
        /** @phpstan-ignore-next-line booleanNot.alwaysFalse
         * ($questionSet->courseID can change when calling $questionSet->load) above */
        if (!Yii::$app->user->can('manageGroup', [
            'courseID' => $questionSet->courseID,
            'semesterID' => Semester::getActualID()
        ])
        ) {
            throw new ForbiddenHttpException(
                Yii::t('app', 'You must be an incumbent instructor of the course to perform this action!')
            );
        }


        if ($questionSet->save(false)) {
            return $questionSet;
        } else {
            throw new ServerErrorHttpException(Yii::t('app', 'A database error occurred'));
        }
    }

    /**
     * Attempts to delete question set if it exists
     * @param int $id is the id of the question set
     *
     * @throws ConflictHttpException
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     * @throws ServerErrorHttpException
     * @throws Throwable
     * @throws StaleObjectException
     *
     * @OA\Delete(
     *     path="/instructor/quiz-question-sets/{id}",
     *     operationId="instructor::QuizQuestonSetsController::actionDelete",
     *     tags={"Instructor Quiz Question Sets"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=204,
     *         description="question set deleted",
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
        $questionSet = QuizQuestionSetResource::findOne($id);

        if (is_null($questionSet)) {
            throw new NotFoundHttpException(Yii::t('app', 'Question set does not exist'));
        }

        if (!Yii::$app->user->can('manageGroup', [
            'courseID' => $questionSet->courseID,
            'semesterID' => Semester::getActualID()
        ])
        ) {
            throw new ForbiddenHttpException(
                Yii::t('app', 'You must be an incumbent instructor of the course to perform this action!')
            );
        }


        try {
            $questionSet->delete();
            $this->response->statusCode = 204;
            return;
        } catch (\yii\db\IntegrityException $e) {
            throw new ConflictHttpException(
                Yii::t('app', 'Cannot delete question set because it is related to a test')
            );
        } catch (\yii\base\ErrorException $e) {
            throw new ServerErrorHttpException(Yii::t('app', 'A database error occurred'));
        }
    }

    /**
     * Copy a question set, its questions and answers
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     * @throws \yii\db\Exception
     *
     * @OA\Post(
     *     path="/instructor/quiz-question-sets/{id}/duplicate",
     *     tags={"Instructor Quiz Question Sets"},
     *     operationId="instructor::QuizQuestonSetsController::actionDuplicate",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *        name="id",
     *        in="path",
     *        required=true,
     *        description="ID of the question set",
     *        @OA\Schema(ref="#/components/schemas/int_id")
     *     ),
     *     @OA\Parameter(ref="#/components/parameters/yii2_fields"),
     *     @OA\Parameter(ref="#/components/parameters/yii2_expand"),
     *     @OA\Response(
     *         response=200,
     *         description="question set duplicated",
     *         @OA\JsonContent(ref="#/components/schemas/Instructor_QuizQuestionSetResource_Read"),
     *     ),
     *    @OA\Response(response=400, ref="#/components/responses/400"),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=403, ref="#/components/responses/403"),
     *    @OA\Response(response=404, ref="#/components/responses/404"),
     *    @OA\Response(response=422, ref="#/components/responses/422"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * )
     */
    public function actionDuplicate(int $id): QuizQuestionSetResource
    {
        $questionSet = QuizQuestionSetResource::findOne($id);
        if (is_null($questionSet)) {
            throw new NotFoundHttpException(Yii::t('app', 'Question set does not exist'));
        }

        if (!Yii::$app->user->can('manageGroup', [
            'courseID' => $questionSet->courseID,
            'semesterID' => Semester::getActualID()
        ])
        ) {
            throw new ForbiddenHttpException(
                Yii::t('app', 'You must be an incumbent instructor of the course to perform this action!')
            );
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $copy = new QuizQuestionSetResource();
            $copy->name = substr($questionSet->name, 0, 90) . ' ' . Yii::t('app', '(copy)');
            $copy->courseID = $questionSet->courseID;
            if (!$copy->save()) {
                throw new \yii\db\Exception("Failed to copy question set (id: $questionSet->id)");
            }

            $batchAnswers = array();
            $answerAttr = ['id', 'text', 'correct', 'questionID'];
            /** @var QuizQuestion[] $questions */
            $questions = $questionSet->getQuestions()->all();
            foreach ($questions as $question) {
                $copyQuestion = new QuizQuestion();
                $copyQuestion->text = $question->text;
                $copyQuestion->questionsetID = $copy->id;
                if (!$copyQuestion->save()) {
                    throw new \yii\db\Exception("Failed to copy question (id: $question->id)");
                }

                /** @var QuizAnswer[] $answers */
                $answers = $question->getAnswers()->all();
                foreach ($answers as $answer) {
                    $batchAnswers[] = [null, $answer->text, $answer->correct, $copyQuestion->id];
                }
            }
            Yii::$app->db->createCommand()->batchInsert(QuizAnswer::tableName(), $answerAttr, $batchAnswers)->execute();

            $transaction->commit();
            return $copy;
        } catch (\Throwable $e) {
            $transaction->rollBack();
            Yii::error(
                "Failed to duplicate question set #{$questionSet->id}." . PHP_EOL .
                "Message: {$e->getMessage()}",
                __METHOD__
            );
            throw new ServerErrorHttpException(Yii::t('app', "Question set duplication failed"));
        }
    }

    /**
     * List uploaded images for a question set
     * @return QuizImageResource[]
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     *
     * @OA\Get(
     *     path="/instructor/quiz-question-sets/{id}/images",
     *     operationId="instructor::QuizQuestonSetsController::actionListImages",
     *     tags={"Instructor Quiz Question Sets"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the question set",
     *         @OA\Schema(ref="#/components/schemas/int_id")
     *     ),
     *     @OA\Parameter(ref="#/components/parameters/yii2_fields"),
     *     @OA\Parameter(ref="#/components/parameters/yii2_expand"),
     *     @OA\Parameter(ref="#/components/parameters/yii2_sort"),
     *
     *     @OA\Response(
     *        response=200,
     *        description="successful operation",
     *        @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Common_QuizImageResource_Read")),
     *    ),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=403, ref="#/components/responses/403"),
     *    @OA\Response(response=404, ref="#/components/responses/404"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * )
     */
    public function actionListImages(int $id): array
    {
        $questionSet = QuizQuestionSetResource::findOne($id);

        if (is_null($questionSet)) {
            throw new NotFoundHttpException(Yii::t('app', 'Question set does not exist'));
        }

        if (!Yii::$app->user->can('manageGroup', [
            'courseID' => $questionSet->courseID,
            'semesterID' => Semester::getActualID()
        ])
        ) {
            throw new ForbiddenHttpException(
                Yii::t('app', 'You must be an incumbent instructor of the course to perform this action!')
            );
        }

        $imageFolder = Yii::getAlias("@appdata/uploadedfiles/examination/$id/");

        if (!file_exists($imageFolder)) {
            return [];
        }

        // Load existing images for questionset
        $files = [];

        $imageFiles = FileHelper::findFiles($imageFolder, ['recursive' => false]);

        foreach ($imageFiles as $filepath) {
            $image = new QuizImageResource();
            $image->name = basename($filepath);
            $image->questionSetID = $questionSet->id;
            $files[] =  $image;
        }

        // Sort files by upload date in descending order
        usort($files, function ($a, $b) {
            $aDate = $a->getUploadDate();
            $bDate = $b->getUploadDate();
            if ($aDate == $bDate) {
                return 0;
            }
            return ($aDate >= $bDate) ? -1 : 1;
        });
        return $files;
    }

    /**
     * Upload images to question set
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     * @throws Exception
     *
     * @OA\Post(
     *     path="/instructor/quiz-question-sets/{id}/images",
     *     operationId="instructor::QuizQuestonSetsController::actionUploadImages",
     *     tags={"Instructor Quiz Question Sets"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *        name="id",
     *        in="path",
     *        required=true,
     *        description="ID of the question set",
     *        @OA\Schema(ref="#/components/schemas/int_id")
     *     ),
     *     @OA\RequestBody(
     *         description="images to upload",
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *               required={"path"},
     *               @OA\Property(type="array",property="path",@OA\Items(type="string",format="binary"))
     *             ),
     *         )
     *     ),
     *     @OA\Response(
     *         response=207,
     *         description="images uploaded",
     *         @OA\JsonContent(ref="#/components/schemas/Instructor_QuizImageUploadResultResource_Read")
     *     ),
     *    @OA\Response(response=400, ref="#/components/responses/400"),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=403, ref="#/components/responses/403"),
     *    @OA\Response(response=422, ref="#/components/responses/422"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * )
     */
    public function actionUploadImages(int $id): QuizImageUploadResultResource
    {
        $questionSet = QuizQuestionSetResource::findOne($id);

        if (is_null($questionSet)) {
            throw new NotFoundHttpException(Yii::t('app', 'Question set does not exist'));
        }

        if (!Yii::$app->user->can('manageGroup', [
            'courseID' => $questionSet->courseID,
            'semesterID' => Semester::getActualID()
        ])
        ) {
            throw new ForbiddenHttpException(
                Yii::t('app', 'You must be an incumbent instructor of the course to perform this action!')
            );
        }

        // Get the files.
        $files = UploadedFile::getInstancesByName('path');

        $uploaded = [];
        $failed = [];
        foreach ($files as $file) {
            $path = Yii::getAlias("@appdata/uploadedfiles/examination/$id/");

            if (!file_exists($path)) {
                FileHelper::createDirectory($path, 0755, true);
            }

            // Try to save the file.
            $saveName = str_replace(
                    ['_', '-'],
                    '0',
                    Yii::$app->security->generateRandomString(12)
                ) . '.' . $file->extension;
            /** @phpstan-ignore-next-line booleanNot.alwaysFalse (YII_ENV_DEV can be either true or false) */
            $result = $file->saveAs($path . $saveName, !YII_ENV_TEST);

            // If the save failed return the error message.
            if ($result) {
                $newImage = new QuizImageResource();
                $newImage->name = $saveName;
                $newImage->questionSetID = $questionSet->id;
                $uploaded[] = $newImage;
            } else {
                // Log
                Yii::error(
                    "Failed to save file to the disc (original: $file->name, saveName: $path $saveName), error code: $file->error",
                    __METHOD__
                );
                $failedResource = new UploadFailedResource();
                $failedResource->name = $file->name;
                $failedResource->cause = [
                    'path' => [Yii::t("app", "Failed to save file. Error logged.")]
                ];
                $failed[] = $failedResource;
            }
        }

        $this->response->statusCode = 207;
        $response = new QuizImageUploadResultResource();
        $response->uploaded = $uploaded;
        $response->failed = $failed;
        return $response;
    }

    /**
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     *
     * @OA\Delete(
     *     path="/instructor/quiz-question-sets/{id}/images/{filename}",
     *     operationId="instructor::QuizQuestonSetsController::actionRemoveImage",
     *     tags={"Instructor Quiz Question Sets"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *        name="id",
     *        in="path",
     *        required=true,
     *        description="ID of the question set",
     *        @OA\Schema(ref="#/components/schemas/int_id")
     *     ),
     *     @OA\Parameter(
     *        name="filename",
     *        in="path",
     *        required=true,
     *        description="Name of the image",
     *        @OA\Schema(type="string"),
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="image deleted",
     *     ),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=403, ref="#/components/responses/403"),
     *    @OA\Response(response=404, ref="#/components/responses/404"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * )
     */
    public function actionRemoveImage(int $id, string $filename): void
    {
        $questionSet = QuizQuestionSetResource::findOne($id);

        if (is_null($questionSet)) {
            throw new NotFoundHttpException(Yii::t('app', 'Question set does not exist'));
        }

        if (!Yii::$app->user->can('manageGroup', [
            'courseID' => $questionSet->courseID,
            'semesterID' => Semester::getActualID()
        ])
        ) {
            throw new ForbiddenHttpException(
                Yii::t('app', 'You must be an incumbent instructor of the course to perform this action!')
            );
        }

        $file = new QuizImageResource();
        $file->name = $filename;
        $file->questionSetID = $id;

        if (!file_exists($file->getFilePath())) {
            throw new NotFoundHttpException(
                Yii::t('app', 'File not found.')
            );
        }

        unlink($file->getFilePath());
        $this->response->statusCode = 204;
    }
}
