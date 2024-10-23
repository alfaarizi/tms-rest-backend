<?php

namespace app\modules\student\controllers;

use app\components\GitManager;
use app\models\Submission;
use app\models\Task;
use app\models\User;
use app\modules\student\resources\SubmissionUploadResource;
use app\modules\student\resources\VerifyItemResource;
use app\resources\AutoTesterResultResource;
use app\models\IpAddress;
use Yii;
use app\modules\student\resources\TaskResource;
use app\modules\student\helpers\PermissionHelpers;
use app\modules\student\resources\SubmissionResource;
use yii\helpers\FileHelper;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;
use yii\web\UploadedFile;

/**
 * This class provides access to student file actions for students
 */
class SubmissionsController extends BaseSubmissionsController
{
    /**
     * @inheritdoc
     */
    protected function verbs()
    {
        return array_merge(parent::verbs(), [
            'view' => ['GET'],
            'download' => ['GET'],
            'upload' => ['POST'],
            'verify' => ['POST'],
            'auto-tester-results' => ['GET'],
            'download-report' => ['GET'],
        ]);
    }

    /**
     * Get information about an uploaded file
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     *
     * @OA\Get(
     *     path="/student/submissions/{id}",
     *     operationId="student::SubmissionsController::actionView",
     *     tags={"Student Student Files"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(ref="#/components/parameters/yii2_fields"),
     *     @OA\Parameter(ref="#/components/parameters/yii2_expand"),
     *     @OA\Parameter(
     *        name="id",
     *        in="path",
     *        required=true,
     *        description="ID of the file",
     *        @OA\Schema(ref="#/components/schemas/int_id"),
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="successful operation",
     *         @OA\JsonContent(ref="#/components/schemas/Student_SubmissionResource_Read"),
     *     ),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=403, ref="#/components/responses/403"),
     *    @OA\Response(response=404, ref="#/components/responses/404"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * ),
     */
    public function actionView(int $id): SubmissionResource
    {
        $file = SubmissionResource::findOne($id);

        if (is_null($file)) {
            throw new NotFoundHttpException(Yii::t('app', 'Student File not found.'));
        }

        PermissionHelpers::isItMyTask($file->taskID);
        PermissionHelpers::isItMySubmission($file);

        return $file;
    }

    /**
     * Download a student file
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     *
     * @OA\Get(
     *     path="/student/submissions/{id}/download",
     *     operationId="student::SubmissionsController::actionDownload",
     *     tags={"Student Student Files"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *        name="id",
     *        in="path",
     *        required=true,
     *        description="ID of the file",
     *        @OA\Schema(ref="#/components/schemas/int_id"),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="successful operation",
     *     ),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=403, ref="#/components/responses/403"),
     *    @OA\Response(response=404, ref="#/components/responses/404"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * ),
     */
    public function actionDownload(int $id): void
    {
        $file = SubmissionResource::findOne($id);

        if (is_null($file)) {
            throw new NotFoundHttpException(Yii::t('app', 'Student File not found.'));
        }

        PermissionHelpers::isItMySubmission($file);

        Yii::$app->response->sendFile($file->path, basename($file->path));
    }

    /**
     * Download test report for student file
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     *
     * @OA\Get(
     *     path="/student/submissions/{id}/download-report",
     *     operationId="student::SubmissionsController::actionDownloadReport",
     *     tags={"Student Student Files"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *        name="id",
     *        in="path",
     *        required=true,
     *        description="ID of the file",
     *        @OA\Schema(ref="#/components/schemas/int_id"),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="successful operation",
     *     ),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=403, ref="#/components/responses/403"),
     *    @OA\Response(response=404, ref="#/components/responses/404"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * ),
     */
    public function actionDownloadReport(int $id): void
    {
        $file = SubmissionResource::findOne($id);

        if (is_null($file)) {
            throw new NotFoundHttpException(Yii::t('app', 'Student File not found.'));
        }

        PermissionHelpers::isItMyTask($file->taskID);
        PermissionHelpers::isItMySubmission($file);

        if (!file_exists($file->reportPath)) {
            throw new NotFoundHttpException(Yii::t('app', 'Test reports not exist for this student file.'));
        }

        Yii::$app->response->sendFile($file->reportPath, basename($file->reportPath));
    }

    /**
     * Upload a new student file
     * @return SubmissionResource|array
     * @throws BadRequestHttpException
     * @throws ForbiddenHttpException
     * @throws ServerErrorHttpException
     * @throws \CzProject\GitPhp\GitException
     *
     * @OA\Post(
     *     path="/student/submissions/upload",
     *     operationId="student::SubmissionsController::actionUpload",
     *     tags={"Student Student Files"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(ref="#/components/parameters/yii2_fields"),
     *     @OA\Parameter(ref="#/components/parameters/yii2_expand"),
     *     @OA\RequestBody(
     *         description="file to upload and taskID",
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(ref="#/components/schemas/Student_SubmissionUploadResource_ScenarioDefault"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="file uploaded",
     *         @OA\JsonContent(ref="#/components/schemas/Student_SubmissionResource_Read"),
     *     ),
     *    @OA\Response(response=400, ref="#/components/responses/400"),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=403, ref="#/components/responses/403"),
     *    @OA\Response(response=422, ref="#/components/responses/422"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * )
     */
    public function actionUpload()
    {
        $model = new SubmissionUploadResource();
        $model->load(Yii::$app->request->post(), '');
        $model->file = UploadedFile::getInstanceByName('file');

        if (!$model->validate()) {
            $this->response->statusCode = 422;
            return $model->errors;
        }

        // Find task
        $task = TaskResource::findOne($model->taskID);

        // Permission checks
        PermissionHelpers::isItMyTask($task->id);
        PermissionHelpers::checkIfTaskAvailable($task);

        // Canvas synchronization check
        if ($task->category == Task::CATEGORY_TYPE_CANVAS_TASKS) {
            throw new BadRequestHttpException(
                Yii::t('app', 'This operation cannot be performed on a canvas synchronized task!')
            );
        }

        // Get previous file
        $prevSubmission = SubmissionResource::findOne(['uploaderID' => Yii::$app->user->id, 'taskID' => $task->id]);

        // Verify that the task is open for submissions or the student has a special late submission permission.
        if (strtotime($task->hardDeadline) < time() && (is_null(
                    $prevSubmission
                ) || $prevSubmission->status !== Submission::STATUS_LATE_SUBMISSION)) {
            throw new BadRequestHttpException(Yii::t('app', 'The hard deadline of the solution has passed!'));
        }

        // Verify that the student has no accepted solution yet.
        if (!is_null($prevSubmission) && $prevSubmission->status === Submission::STATUS_ACCEPTED) {
            throw new BadRequestHttpException(Yii::t('app', 'Your solution was accepted!'));
        }

        return $this->saveFile(
            $prevSubmission,
            $model->file,
            $task->id,
            Yii::$app->params['versionControl']['enabled'] && $task->isVersionControlled
        );
    }

    /**
     * Save the files to the disk (and to the git repository)
     * @throws ServerErrorHttpException
     * @throws \CzProject\GitPhp\GitException
     */
    private function saveFile(SubmissionResource $prevSubmission, UploadedFile $newFile, int $taskID, bool $versionControlled): SubmissionResource
    {
        /** @var User $user */
        $user = Yii::$app->user->identity;
        // Set basepath
        $basepath = Yii::getAlias("@appdata/uploadedfiles/$taskID/")
            . strtolower($user->userCode) . '/';

        if (!file_exists($basepath)) {
            FileHelper::createDirectory($basepath, 0755, true);
        }

        $zipFiles = FileHelper::findFiles($basepath, ['only' => ['*.zip'], 'recursive' => false]);
        if ($zipFiles != null) {
            unlink($zipFiles[0]);
        }

        // Save file to disc.
        $result = $newFile->saveAs($basepath . $newFile->name, !YII_ENV_TEST);
        if (!$result) {
            // Log
            Yii::error(
                "Failed to save file to the disc ($newFile->name), error code: $newFile->error",
                __METHOD__
            );
            throw new ServerErrorHttpException(Yii::t('app', "Failed to save file. Error logged."));
        }

        if ($versionControlled) {
            // Find unique string
            $dirs = FileHelper::findDirectories($basepath, ['recursive' => false]);
            rsort($dirs);
            $repopath = $basepath . basename($dirs[0]) . '/';
            $zipPath = $basepath . $newFile->name;
            GitManager::uploadToRepo($repopath, $zipPath);
        }

        $submission = $prevSubmission;
        $submission->uploadCount++;
        $submission->name = basename($newFile->name);
        $submission->uploadTime = date('Y-m-d H:i:s');
        $submission->status = Submission::STATUS_UPLOADED;
        $submission->verified = !$submission->task->passwordProtected;
        $submission->autoTesterStatus = Submission::AUTO_TESTER_STATUS_NOT_TESTED;

        if ($submission->save()) {
            Yii::info(
                "A new solution has been uploaded for " .
                "{$submission->task->name} ($taskID)",
                __METHOD__
            );
            $ipAddress = new IpAddress();
            $ipAddress->submissionId = $submission->id;
            $ipAddress->ipAddress = $this->request->userIP;
            if(!$ipAddress->save()) throw new ServerErrorHttpException(Yii::t('app', "A database error occurred"));
            return $submission;
        } else {
            throw new ServerErrorHttpException(Yii::t('app', "A database error occurred"));
        }
    }

    /**
     *
     * @OA\Post(
     *     path="/student/submissions/verify",
     *     operationId="student::SubmissionsController::actionVerify",
     *     tags={"Student Student Files"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         description="student file id and password to verify file",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(ref="#/components/schemas/Student_VerifyItemResource_ScenarioDefault"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="verified",
     *         @OA\JsonContent(ref="#/components/schemas/Student_SubmissionResource_Read"),
     *     ),
     *    @OA\Response(response=400, ref="#/components/responses/400"),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=403, ref="#/components/responses/403"),
     *    @OA\Response(response=404, ref="#/components/responses/404"),
     *    @OA\Response(response=422, ref="#/components/responses/422"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * )

     * @throws BadRequestHttpException
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     * @throws ServerErrorHttpException
     */
    public function actionVerify()
    {
        $verifyResource = new VerifyItemResource();
        $verifyResource->load(Yii::$app->request->post(), '');

        if (!$verifyResource->validate()) {
            $this->response->statusCode = 422;
            return $verifyResource->errors;
        }

        $file = SubmissionResource::findOne($verifyResource->id);

        if (is_null($file)) {
            throw new NotFoundHttpException(Yii::t('app', 'Student File not found.'));
        }

        PermissionHelpers::isItMySubmission($file);

        if ($file->verified) {
            throw new BadRequestHttpException(Yii::t('app', 'Student file is already verified'));
        }

        $currentIp = $this->request->userIP;
        $uploadAddresses = $file->ipAddresses;
        $uploadAddressesStringList = implode(", ", $uploadAddresses);
        if (!$verifyResource->disableIpCheck) {
            if (count($uploadAddresses) > 1 || $uploadAddresses[0] !== $currentIp) {
                $verifyResource->addError(
                    'disableIpCheck',
                    Yii::t(
                        'app',
                        'The current IP address and the IP address used for the file upload do not match. Current address: {currentIp}. Addresses of uploads: {uploadAddresses}.',
                        [
                            'currentIp' => $currentIp,
                            'uploadAddresses' => $uploadAddressesStringList
                        ]
                    )
                );
            }
        }

        if ($verifyResource->password !== $file->task->password) {
            $verifyResource->addError('password', Yii::t('app', 'Invalid password'));
        }

        if ($verifyResource->hasErrors()) {
            $this->response->statusCode = 422;
            return $verifyResource->errors;
        }

        $file->verified = true;
        if ($file->save()) {
            Yii::info("A student file (#$file->id) has been verified. Upload IP addresses: $uploadAddressesStringList.", __METHOD__);
            return $file;
        } else {
            throw new ServerErrorHttpException(Yii::t('app', "A database error occurred"));
        }
    }

    /**
     * Get information about an uploaded file's autotester result
     * @return AutoTesterResultResource[]
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     *
     * @OA\Get(
     *     path="/student/submissions/{id}/auto-tester-results",
     *     operationId="student::SubmissionsController::actionAutoTesterResults",
     *     tags={"Student Student Files"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(ref="#/components/parameters/yii2_fields"),
     *     @OA\Parameter(ref="#/components/parameters/yii2_expand"),
     *     @OA\Parameter(
     *        name="id",
     *        in="path",
     *        required=true,
     *        description="ID of the file",
     *        @OA\Schema(ref="#/components/schemas/int_id"),
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="successful operation",
     *         @OA\JsonContent(ref="#/components/schemas/Common_AutoTesterResultResource_Read"),
     *     ),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=403, ref="#/components/responses/403"),
     *    @OA\Response(response=404, ref="#/components/responses/404"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * ),
     */
    public function actionAutoTesterResults(int $id): array
    {
        $file = SubmissionResource::findOne($id);

        if (is_null($file)) {
            throw new NotFoundHttpException(Yii::t('app', 'Student File not found.'));
        }

        PermissionHelpers::isItMyTask($file->taskID);
        PermissionHelpers::isItMySubmission($file);

        $results = $file->testResults;

        $idx = 1;
        return array_map(function ($result) use (&$idx) {
            return new AutoTesterResultResource(
                $idx++,
                $result->isPassed,
                $result->safeErrorMsg,
            );
        }, $results);
    }
}
