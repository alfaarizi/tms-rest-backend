<?php

namespace app\controllers;

use app\components\GitManager;
use Yii;
use app\models\StudentFile;
use app\models\User;
use yii\helpers\FileHelper;
use yii\filters\AccessControl;

/**
 * This class controls the git actions
 */
class GitController extends BaseRestController
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();

        unset($behaviors['authenticator']);

        $behaviors['access'] = [
            'class' => AccessControl::class,
            'rules' => [
                [
                    'allow' => true,
                    'ips' => ['127.0.0.1', $_SERVER['SERVER_ADDR']],
                ],
            ],
        ];

        return $behaviors;
    }

    /**
     * Saves the student file to database.
     * This action is called from post-receive git hooks and only accessible from localhost.
     * @param int $taskid is the id of the task
     * @param int $studentid is the id of the student
     *
     * @OA\Get(
     *     path="/git/git-push",
     *     operationId="local::GitController::actionGitPush",
     *     tags={"Local Git"},
     *
     *     @OA\Parameter(
     *      name="taskid",
     *      in="query",
     *      required=true,
     *      description="ID of the task",
     *      explode=true,
     *      @OA\Schema(ref="#/components/schemas/int_id"),
     *    ),
     *     @OA\Parameter(
     *      name="studentid",
     *      in="query",
     *      required=true,
     *      description="userID of the student",
     *      explode=true,
     *      @OA\Schema(ref="#/components/schemas/int_id"),
     *    ),
     *    @OA\Response(
     *       response=200,
     *       description="successful operation",
     *       @OA\JsonContent(type="string"),
     *    ),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * )
     */
    public function actionGitPush($taskid, $studentid)
    {
        $studentfile = StudentFile::findOne(['taskID' => $taskid, 'uploaderID' => $studentid]);
        $student = User::findOne($studentid);
        Yii::$app->language = $student->locale;
        if ($studentfile == null) {
            // Zip the files from the solution
            GitManager::createZip($taskid, $studentid);
            $studentfile = new StudentFile();
            // Set details
            $studentfile->taskID = $taskid;
            $studentfile->isAccepted = StudentFile::IS_ACCEPTED_UPLOADED;
            $studentfile->evaluatorStatus = StudentFile::EVALUATOR_STATUS_NOT_TESTED;
            $studentfile->uploaderID = $studentid;
            $studentfile->name = strtolower($student->neptun) . '.zip';
            $studentfile->grade = null;
            $studentfile->notes = "";
            $studentfile->uploadTime = date('Y-m-d H:i:s');
            $studentfile->isVersionControlled = 1;
            $studentfile->uploadCount = 1;
            // Save it to the db.
            if ($studentfile->save()) {
                Yii::info(
                    "A new solution has been uploaded for " .
                    "{$studentfile->task->name} ($studentfile->taskID)",
                    __METHOD__
                );
                $this->response->statusCode = 201;
                return Yii::t('app', 'Upload completed.');
            } elseif ($studentfile->hasErrors()) {
                $this->response->statusCode = 422;
                return $studentfile->errors;
            } else {
                $this->response->statusCode = 500;
                return Yii::t('app', "A database error occurred");
            }
        } else {
            // Delete the previous zip file
            $basepath = Yii::$app->basePath . '/' . Yii::$app->params['data_dir'] . '/uploadedfiles/' . $taskid . '/' . $student->neptun;
            $basefiles = FileHelper::findFiles($basepath, ['only' => ['*.zip'], 'recursive' => false]);
            if ($basefiles != null) {
                unlink($basefiles[0]);
            }
            // Zip the files from the solution
            GitManager::createZip($taskid, $studentid);
            // Set details
            $studentfile->name = strtolower($student->neptun) . '.zip';
            $studentfile->uploadTime = date('Y-m-d H:i:s');
            $studentfile->isAccepted = StudentFile::IS_ACCEPTED_UPLOADED;
            $studentfile->evaluatorStatus = StudentFile::EVALUATOR_STATUS_NOT_TESTED;
            $studentfile->uploadCount++;
            // Save it to the db.
            if ($studentfile->save()) {
                Yii::info(
                    "A new solution has been uploaded for " .
                    "{$studentfile->task->name} ($studentfile->taskID)",
                    __METHOD__
                );
                $this->response->statusCode = 200;
                return Yii::t('app', 'Upload completed.');
            } elseif ($studentfile->hasErrors()) {
                $this->response->statusCode = 422;
                return $studentfile->errors;
            } else {
                $this->response->statusCode = 500;
                return Yii::t('app', "A database error occurred");
            }
        }
    }
}
