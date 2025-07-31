<?php

namespace app\controllers;

use app\components\GitManager;
use app\components\StructuralRequirementChecker;
use app\modules\instructor\resources\TaskResource;
use Yii;
use app\models\Submission;
use app\models\User;
use yii\helpers\FileHelper;
use yii\filters\AccessControl;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;

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
        $submission = Submission::findOne(['taskID' => $taskid, 'uploaderID' => $studentid]);
        $student = User::findOne($studentid);
        Yii::$app->language = $student->locale;
        if ($submission == null) {
            // Zip the files from the solution
            GitManager::createZip($taskid, $studentid);
            $submission = new Submission();
            // Set details
            $submission->taskID = $taskid;
            $submission->status = Submission::STATUS_UPLOADED;
            $submission->autoTesterStatus = Submission::AUTO_TESTER_STATUS_NOT_TESTED;
            $submission->uploaderID = $studentid;
            $submission->name = strtolower($student->userCode) . '.zip';
            $submission->grade = null;
            $submission->notes = "";
            $submission->uploadTime = date('Y-m-d H:i:s');
            $submission->isVersionControlled = true;
            $submission->uploadCount = 1;
            $submission->verified = true;
            // Save it to the db.
            if ($submission->save()) {
                GitManager::updateSubmissionHooks($submission);
                Yii::info(
                    "A new solution has been uploaded for " .
                    "{$submission->task->name} ($submission->taskID)",
                    __METHOD__
                );
                $this->response->statusCode = 201;
                return Yii::t('app', 'Upload completed.');
            } elseif ($submission->hasErrors()) {
                $this->response->statusCode = 422;
                return $submission->errors;
            } else {
                $this->response->statusCode = 500;
                return Yii::t('app', "A database error occurred");
            }
        } else {
            // Delete the previous zip file
            $basepath = Yii::getAlias("@appdata/uploadedfiles/$taskid/") . $student->userCode;
            $basefiles = FileHelper::findFiles($basepath, ['only' => ['*.zip'], 'recursive' => false]);
            if ($basefiles != null) {
                unlink($basefiles[0]);
            }
            // Zip the files from the solution
            GitManager::createZip($taskid, $studentid);
            // Set details
            $submission->name = strtolower($student->userCode) . '.zip';
            $submission->uploadTime = date('Y-m-d H:i:s');
            $submission->status = Submission::STATUS_UPLOADED;
            $submission->autoTesterStatus = Submission::AUTO_TESTER_STATUS_NOT_TESTED;
            $submission->uploadCount++;
            $submission->verified = true;
            // Save it to the db.
            if ($submission->save()) {
                GitManager::updateSubmissionHooks($submission);
                Yii::info(
                    "A new solution has been uploaded for " .
                    "{$submission->task->name} ($submission->taskID)",
                    __METHOD__
                );
                $this->response->statusCode = 200;
                return Yii::t('app', 'Upload completed.');
            } elseif ($submission->hasErrors()) {
                $this->response->statusCode = 422;
                return $submission->errors;
            } else {
                $this->response->statusCode = 500;
                return Yii::t('app', "A database error occurred");
            }
        }
    }

    /**
     * Checks if the given paths conform to structural requirements.
     * This action is called from pre-receive git hooks and only accessible from localhost.
     * @param int $taskId is the id of the task
     *
     * @OA\Post(
     *     path="/git/check-structural-requirements",
     *     operationId="local::GitController::actionCheckStructuralRequirements",
     *     tags={"Local Git"},
     *     @OA\Parameter(
     *          name="taskId",
     *          in="query",
     *          required=true,
     *          @OA\Schema(type="integer")
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(
     *                  property="paths",
     *                  type="array",
     *                  @OA\Items(type="string")
     *              )
     *          )
     *      ),
     *    @OA\Response(
     *       response=204,
     *       description="structural requirements met",
     *       @OA\JsonContent(type="string"),
     *    ),
     *    @OA\Response(
     *        response=422,
     *        description="structural requirements not met",
     *        @OA\JsonContent(type="string"),
     *     ),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * )
     * @throws NotFoundHttpException
     * @throws BadRequestHttpException
     */
    public function actionCheckStructuralRequirements(int $taskId)
    {
        $task = TaskResource::findOne($taskId);

        if (is_null($task)) {
            throw new NotFoundHttpException(Yii::t('app', 'Task not found.'));
        }

        $paths = Yii::$app->request->post('paths', []);

        if (empty($paths) || !is_array($paths)) {
            throw new BadRequestHttpException("Paths must be an array of strings.");
        }

        $structuralRequirementResult = StructuralRequirementChecker::validatePaths($task->structuralRequirements, $paths);
        $errorMsg = "";

        if (!empty($structuralRequirementResult['includeErrors'])) {
            $errorMsg = Yii::t('app', 'TMS structural analyzer found issues in the uploaded solution.');
            $errorMsg .= implode(' ', $structuralRequirementResult['includeErrors']);
        }

        if (!empty($structuralRequirementResult['excludeErrors'])) {
            $errorMsg = empty($errorMsg)
                ? Yii::t('app', 'TMS structural analyzer found issues in the uploaded solution.')
                : $errorMsg . ' ';
            $errorMsg .= implode(' ', $structuralRequirementResult['excludeErrors']);
        }

        if (!empty($errorMsg)) {
            $this->response->statusCode = 422;
        } else {
            $this->response->statusCode = 204;
        }

        return empty($errorMsg) ? null : $errorMsg;
    }
}
