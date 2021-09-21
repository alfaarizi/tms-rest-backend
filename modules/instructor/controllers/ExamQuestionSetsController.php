<?php

namespace app\modules\instructor\controllers;

use app\models\Semester;
use app\models\ExamAnswer;
use app\models\ExamQuestion;
use app\models\ExamTestInstanceQuestion;
use app\resources\ExamImageResource;
use app\modules\instructor\resources\ExamQuestionSetResource;
use app\resources\SemesterResource;
use Yii;
use yii\data\ActiveDataProvider;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;
use yii\web\BadRequestHttpException;
use yii\web\ConflictHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;
use yii\web\UploadedFile;

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
     * @return ActiveDataProvider
     */
    public function actionIndex()
    {
        // Question sets for owned courses
        $questionSetsByCourse = ExamQuestionSetResource::find()
            ->listForOwnedCourses(Yii::$app->user->id);

        // Question sets for owned groups (only for active semester!)
        $questionSetsByGroup = ExamQuestionSetResource::find()
            ->listForOwnedGroups(Yii::$app->user->id, SemesterResource::getActualID());

        return new ActiveDataProvider([
           'query' => $questionSetsByCourse->union($questionSetsByGroup),
           'sort' => false,
           'pagination' => false,
       ]);
    }

    /**
     * @param $id
     * @return ExamQuestionSetResource
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     */
    public function actionView($id) {
        $questionSet = ExamQuestionSetResource::findOne($id);

        if (is_null($questionSet)) {
            throw new NotFoundHttpException(Yii::t('app', 'Question set does not exist'));
        }

        if (!Yii::$app->user->can('manageGroup', [
            'courseID' => $questionSet->courseID,
            'semesterID' => Semester::getActualID()])
        ) {
            throw new ForbiddenHttpException(Yii::t('app', 'You must be an incumbent instructor of the course to perform this action!'));
        }

        return $questionSet;
    }


    /**
     * @return ExamQuestionSetResource|array
     * @throws ForbiddenHttpException
     * @throws ServerErrorHttpException
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
            'semesterID' => Semester::getActualID()])
        ) {
            throw new ForbiddenHttpException(Yii::t('app', 'You must be an incumbent instructor of the course to perform this action!'));
        }

        if ($model->save(false)) {
            $this->response->statusCode = 201;
            return $model;
        } else {
            throw new ServerErrorHttpException(Yii::t('app', 'A database error occurred'));
        }
    }

    /**
     * @param int $id
     */
    public function actionUpdate($id) {
        $questionSet = ExamQuestionSetResource::findOne($id);

        if (is_null($questionSet)) {
            throw new NotFoundHttpException(Yii::t('app', 'Question set does not exist'));
        }

        // Check permissions for the original object
        if (!Yii::$app->user->can('manageGroup', [
            'courseID' => $questionSet->courseID,
            'semesterID' => Semester::getActualID()])
        ) {
            throw new ForbiddenHttpException(Yii::t('app', 'You must be an incumbent instructor of the course to perform this action!'));
        }

        if(!empty(ExamTestInstanceQuestion::find()->where(["in", "questionID", $questionSet->getQuestions()->select("id")])->all()) ) {
            throw new ConflictHttpException(Yii::t('app', 'Cannot update question set because a related test was finalized'));
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
            'semesterID' => Semester::getActualID()])
        ) {
            throw new ForbiddenHttpException(Yii::t('app', 'You must be an incumbent instructor of the course to perform this action!'));
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
     */
    public function actionDelete($id)
    {
        $questionSet = ExamQuestionSetResource::findOne($id);

        if (is_null($questionSet)) {
            throw new NotFoundHttpException(Yii::t('app', 'Question set does not exist'));
        }

        if (!Yii::$app->user->can('manageGroup', [
            'courseID' => $questionSet->courseID,
            'semesterID' => Semester::getActualID()])
        ) {
            throw new ForbiddenHttpException(Yii::t('app', 'You must be an incumbent instructor of the course to perform this action!'));
        }


        try {
            $questionSet->delete();
            $this->response->statusCode = 204;
            return;
        } catch (yii\db\IntegrityException $e) {
            throw new ConflictHttpException(Yii::t('app', 'Cannot delete question set because it is related to a test'));
        } catch (yii\base\ErrorException $e) {
            throw new ServerErrorHttpException( Yii::t('app', 'A database error occurred'));
        }
    }

    /**
     * Copies a question set, it's questions and answers
     * @param int $id
     * @return ExamQuestionSetResource
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     * @throws \yii\db\Exception
     */
    public function actionDuplicate($id)
    {
        $questionSet = ExamQuestionSetResource::findOne($id);
        if(is_null($questionSet)) {
            throw new NotFoundHttpException(Yii::t('app', 'Question set does not exist'));
        }

        if (!Yii::$app->user->can('manageGroup', [
            'courseID' => $questionSet->courseID,
            'semesterID' => Semester::getActualID()])
        ) {
            throw new ForbiddenHttpException(Yii::t('app', 'You must be an incumbent instructor of the course to perform this action!'));
        }

        $copy = new ExamQuestionSetResource();
        $copy->name = $questionSet->name . ' ' . Yii::t('app', '(copy)');
        $copy->courseID = $questionSet->courseID;
        $copy->save();
        $batchAnswers = array();
        $answerAttr = ['id', 'text', 'correct', 'questionID'];
        foreach($questionSet->getQuestions()->all() as $question) {
            $copyQuestion = new ExamQuestion();
            $copyQuestion->text = $question->text;
            $copyQuestion->questionsetID = $copy->id;
            $copyQuestion->save();
            foreach($question->getAnswers()->all() as $answer){
                $batchAnswers[] = [null, $answer->text, $answer->correct, $copyQuestion->id];
            }
        }
        Yii::$app->db->createCommand()->batchInsert(ExamAnswer::tableName(), $answerAttr, $batchAnswers)->execute();

        return $copy;
    }

    /**
     * @param int $id
     * @return array
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     */
    public function actionListImages($id)
    {
        $questionSet = ExamQuestionSetResource::findOne($id);

        if(is_null($questionSet)) {
            throw new NotFoundHttpException(Yii::t('app', 'Question set does not exist'));
        }

        if (!Yii::$app->user->can('manageGroup', [
            'courseID' => $questionSet->courseID,
            'semesterID' => Semester::getActualID()])
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
            $filename = basename($filepath);
            $files[] = new ExamImageResource($filename, $questionSet->id);
        }

        // Sort files by upload date in descending order
        usort($files, function($a, $b) {
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
     * @param int $id
     * @return array[]
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     * @throws \yii\base\Exception
     */
    public function actionUploadImages($id)
    {
        $questionSet = ExamQuestionSetResource::findOne($id);

        if(is_null($questionSet)) {
            throw new NotFoundHttpException(Yii::t('app', 'Question set does not exist'));
        }

        if (!Yii::$app->user->can('manageGroup', [
            'courseID' => $questionSet->courseID,
            'semesterID' => Semester::getActualID()])
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
            $saveName = str_replace(['_', '-'], '0', Yii::$app->security->generateRandomString(12)) . '.' . $file->extension;
            $result = $file->saveAs($path . $saveName, !YII_ENV_TEST);

            // If the save failed return the error message.
            if ($result) {
                $uploaded[] = new ExamImageResource($saveName, $questionSet->id);
            } else {
                // Log
                Yii::error(
                    "Failed to save file to the disc (original: $file->name, saveName: $path $saveName), error code: $file->error",
                    __METHOD__
                );
                $failed[] = [
                    'name' => $file->name,
                    'failed' => [
                        'path' => [Yii::t("app", "Failed to save file. Error logged.")]
                    ]
                ];
            }
        }

        $this->response->statusCode = 207;
        return [
            'uploaded' => $uploaded,
            'failed' => $failed
        ];
    }

    /**
     * @param int $id
     * @param string $filename
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     */
    public function actionRemoveImage($id, $filename)
    {
        $questionSet = ExamQuestionSetResource::findOne($id);

        if(is_null($questionSet)) {
            throw new NotFoundHttpException(Yii::t('app', 'Question set does not exist'));
        }

        if (!Yii::$app->user->can('manageGroup', [
            'courseID' => $questionSet->courseID,
            'semesterID' => Semester::getActualID()])
        ) {
            throw new ForbiddenHttpException(
                Yii::t('app', 'You must be an incumbent instructor of the course to perform this action!')
            );
        }

        $file = new ExamImageResource($filename, $id);

        if (!file_exists($file->getFilePath())) {
            throw new NotFoundHttpException(
                Yii::t('app', 'File not found.')
            );
        }

        unlink($file->getFilePath());
        $this->response->statusCode = 204;
    }
}
