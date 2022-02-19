<?php

namespace app\modules\instructor\controllers;

use app\models\ExamAnswer;
use app\models\ExamQuestion;
use app\models\ExamTestInstanceQuestion;
use app\models\Semester;
use app\modules\instructor\resources\ExamImageUploadResultResource;
use app\modules\instructor\resources\ExamQuestionSetResource;
use app\modules\instructor\resources\UploadFailedResource;
use app\resources\ExamImageResource;
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
 *   path="/instructor/exam-question-sets/{id}",
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
 * This class provides access to exam questionsets for instructors
 */
class ExamQuestionSetsController extends BaseInstructorRestController
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
     * @return ActiveDataProvider
     *
     * @OA\Get(
     *     path="/instructor/exam-question-sets",
     *     tags={"Instructor Exam Question Sets"},
     *     operationId="instructor::ExamQuestonSetsController::actionIndex",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(ref="#/components/parameters/yii2_fields"),
     *     @OA\Parameter(ref="#/components/parameters/yii2_expand"),
     *     @OA\Parameter(ref="#/components/parameters/yii2_sort"),
     *
     *     @OA\Response(
     *        response=200,
     *        description="successful operation",
     *        @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Instructor_ExamQuestionSetResource_Read")),
     *    ),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * ),
     */
    public function actionIndex()
    {
        // Question sets for owned courses
        $questionSetsByCourse = ExamQuestionSetResource::find()
            ->listForOwnedCourses(Yii::$app->user->id);

        // Question sets for owned groups (only for active semester!)
        $questionSetsByGroup = ExamQuestionSetResource::find()
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
     * @param $id
     * @return ExamQuestionSetResource
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     *
     * @OA\Get(
     *     path="/instructor/exam-question-sets/{id}",
     *     tags={"Instructor Exam Question Sets"},
     *     operationId="instructor::ExamQuestonSetsController::actionView",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(ref="#/components/parameters/yii2_fields"),
     *     @OA\Parameter(ref="#/components/parameters/yii2_expand"),
     *     @OA\Response(
     *         response=200,
     *         description="successful operation",
     *         @OA\JsonContent(ref="#/components/schemas/Instructor_ExamQuestionSetResource_Read"),
     *     ),
     *    @OA\Response(response=400, ref="#/components/responses/400"),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=404, ref="#/components/responses/404"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * )
     */
    public function actionView($id)
    {
        $questionSet = ExamQuestionSetResource::findOne($id);

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
     * @return ExamQuestionSetResource|array
     * @throws ForbiddenHttpException
     * @throws ServerErrorHttpException
     *
     * @OA\Post(
     *     path="/instructor/exam-question-sets",
     *     tags={"Instructor Exam Question Sets"},
     *     operationId="instructor::ExamQuestonSetsController::actionCreate",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(ref="#/components/parameters/yii2_fields"),
     *     @OA\Parameter(ref="#/components/parameters/yii2_expand"),
     *     @OA\RequestBody(
     *         description="new question set",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(ref="#/components/schemas/Instructor_ExamQuestionSetResource_ScenarioCreate"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="new question set created",
     *         @OA\JsonContent(ref="#/components/schemas/Instructor_ExamQuestionSetResource_Read"),
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
        $model = new ExamQuestionSetResource();
        $model->scenario = ExamQuestionSetResource::SCENARIO_CREATE;
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
     * @param int $id
     * @return ExamQuestionSetResource|array
     * @throws ConflictHttpException
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     * @throws ServerErrorHttpException
     *
     * @OA\Put(
     *     path="/instructor/exam-question-sets/{id}",
     *     operationId="instructor::ExamQuestonSetsController::actionUpdate",
     *     tags={"Instructor Exam Question Sets"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(ref="#/components/parameters/yii2_fields"),
     *     @OA\Parameter(ref="#/components/parameters/yii2_expand"),
     *     @OA\RequestBody(
     *         description="updated question set",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(ref="#/components/schemas/Instructor_ExamQuestionSetResource_ScenarioUpdate"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="question set updated",
     *         @OA\JsonContent(ref="#/components/schemas/Instructor_ExamQuestionSetResource_Read"),
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
    public function actionUpdate($id)
    {
        $questionSet = ExamQuestionSetResource::findOne($id);

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
            ExamTestInstanceQuestion::find()
                ->where(["in", "questionID", $questionSet->getQuestions()->select("id")])
                ->all()
        )) {
            throw new ConflictHttpException(
                Yii::t('app', 'Cannot update question set because a related test was finalized')
            );
        }

        $questionSet->scenario = ExamQuestionSetResource::SCENARIO_UPDATE;
        $questionSet->load(Yii::$app->request->post(), '');

        if (!$questionSet->validate()) {
            $this->response->statusCode = 422;
            return $questionSet->errors;
        }

        // Check permissions for the modified object (courseID can be changed)
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
     *     path="/instructor/exam-question-sets/{id}",
     *     operationId="instructor::ExamQuestonSetsController::actionDelete",
     *     tags={"Instructor Exam Question Sets"},
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
    public function actionDelete($id)
    {
        $questionSet = ExamQuestionSetResource::findOne($id);

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
        } catch (yii\db\IntegrityException $e) {
            throw new ConflictHttpException(
                Yii::t('app', 'Cannot delete question set because it is related to a test')
            );
        } catch (yii\base\ErrorException $e) {
            throw new ServerErrorHttpException(Yii::t('app', 'A database error occurred'));
        }
    }

    /**
     * Copy a question set, it's questions and answers
     * @param int $id
     * @return ExamQuestionSetResource
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     * @throws \yii\db\Exception
     *
     * @OA\Post(
     *     path="/instructor/exam-question-sets/{id}/duplicate",
     *     tags={"Instructor Exam Question Sets"},
     *     operationId="instructor::ExamQuestonSetsController::actionDuplicate",
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
     *         @OA\JsonContent(ref="#/components/schemas/Instructor_ExamQuestionSetResource_Read"),
     *     ),
     *    @OA\Response(response=400, ref="#/components/responses/400"),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=403, ref="#/components/responses/403"),
     *    @OA\Response(response=404, ref="#/components/responses/404"),
     *    @OA\Response(response=422, ref="#/components/responses/422"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * )
     */
    public function actionDuplicate($id)
    {
        $questionSet = ExamQuestionSetResource::findOne($id);
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

        $copy = new ExamQuestionSetResource();
        $copy->name = $questionSet->name . ' ' . Yii::t('app', '(copy)');
        $copy->courseID = $questionSet->courseID;
        $copy->save();
        $batchAnswers = array();
        $answerAttr = ['id', 'text', 'correct', 'questionID'];
        foreach ($questionSet->getQuestions()->all() as $question) {
            $copyQuestion = new ExamQuestion();
            $copyQuestion->text = $question->text;
            $copyQuestion->questionsetID = $copy->id;
            $copyQuestion->save();
            foreach ($question->getAnswers()->all() as $answer) {
                $batchAnswers[] = [null, $answer->text, $answer->correct, $copyQuestion->id];
            }
        }
        Yii::$app->db->createCommand()->batchInsert(ExamAnswer::tableName(), $answerAttr, $batchAnswers)->execute();

        return $copy;
    }

    /**
     * List uploaded images for a question set
     * @param int $id
     * @return array
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     *
     * @OA\Get(
     *     path="/instructor/exam-question-sets/{id}/images",
     *     operationId="instructor::ExamQuestonSetsController::actionListImages",
     *     tags={"Instructor Exam Question Sets"},
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
     *        @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Common_ExamImageResource_Read")),
     *    ),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=403, ref="#/components/responses/403"),
     *    @OA\Response(response=404, ref="#/components/responses/404"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * )
     */
    public function actionListImages($id)
    {
        $questionSet = ExamQuestionSetResource::findOne($id);

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

        $imageFolder = Yii::$app->basePath . '/' . Yii::$app->params['data_dir'] . '/uploadedfiles/examination/' . $id . '/';

        if (!file_exists($imageFolder)) {
            return [];
        }

        // Load existing images for questionset
        $files = [];

        $imageFiles = FileHelper::findFiles($imageFolder, ['recursive' => false]);

        foreach ($imageFiles as $filepath) {
            $image = new ExamImageResource();
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
     * @param int $id
     * @return ExamImageUploadResultResource
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     * @throws Exception
     *
     * @OA\Post(
     *     path="/instructor/exam-question-sets/{id}/images",
     *     operationId="instructor::ExamQuestonSetsController::actionUploadImages",
     *     tags={"Instructor Exam Question Sets"},
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
     *         @OA\JsonContent(ref="#/components/schemas/Instructor_ExamImageUploadResultResource_Read")
     *     ),
     *    @OA\Response(response=400, ref="#/components/responses/400"),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=403, ref="#/components/responses/403"),
     *    @OA\Response(response=422, ref="#/components/responses/422"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * )
     */
    public function actionUploadImages($id)
    {
        $questionSet = ExamQuestionSetResource::findOne($id);

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
            $path = Yii::$app->basePath . '/' . Yii::$app->params['data_dir'] . '/uploadedfiles/examination/' . $id . '/';

            if (!file_exists($path)) {
                mkdir($path, 0755, true);
            }

            // Try to save the file.
            $saveName = str_replace(
                    ['_', '-'],
                    '0',
                    Yii::$app->security->generateRandomString(12)
                ) . '.' . $file->extension;
            $result = $file->saveAs($path . $saveName, !YII_ENV_TEST);

            // If the save failed return the error message.
            if ($result) {
                $newImage = new ExamImageResource();
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
        $response = new ExamImageUploadResultResource();
        $response->uploaded = $uploaded;
        $response->failed = $failed;
        return $response;
    }

    /**
     * @param int $id
     * @param string $filename
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     *
     * @OA\Delete(
     *     path="/instructor/exam-question-sets/{id}/images/{filename}",
     *     operationId="instructor::ExamQuestonSetsController::actionRemoveImage",
     *     tags={"Instructor Exam Question Sets"},
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
    public function actionRemoveImage($id, $filename)
    {
        $questionSet = ExamQuestionSetResource::findOne($id);

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

        $file = new ExamImageResource();
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
