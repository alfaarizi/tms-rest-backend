<?php

namespace app\modules\student\controllers;

use app\components\GitManager;
use app\models\Log;
use app\models\StudentFile;
use app\models\User;
use app\modules\student\resources\StudentFileUploadResource;
use app\modules\student\resources\VerifyItemResource;
use Yii;
use app\modules\student\resources\TaskResource;
use app\modules\student\helpers\PermissionHelpers;
use app\modules\student\resources\StudentFileResource;
use yii\helpers\FileHelper;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;
use yii\web\UploadedFile;

/**
 * This class provides access to student file actions for students
 */
class StudentFilesController extends BaseStudentRestController
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
        ]);
    }

    /**
     * Get information about an uploaded file
     * @param int $id
     * @return StudentFileResource|null
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     *
     * @OA\Get(
     *     path="/student/student-files/{id}",
     *     operationId="student::StudentFilesController::actionView",
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
     *         @OA\JsonContent(ref="#/components/schemas/Student_StudentFileResource_Read"),
     *     ),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=403, ref="#/components/responses/403"),
     *    @OA\Response(response=404, ref="#/components/responses/404"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * ),
     */
    public function actionView($id)
    {
        $file = StudentFileResource::findOne($id);

        if (is_null($file)) {
            throw new NotFoundHttpException(Yii::t('app', 'Student File not found.'));
        }

        PermissionHelpers::isItMyTask($file->taskID);
        PermissionHelpers::isItMyStudentFile($file);

        return $file;
    }

    /**
     * Download a student file
     * @param int $id
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     *
     * @OA\Get(
     *     path="/student/student-files/{id}/download",
     *     operationId="student::StudentFilesController::actionDownload",
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
    public function actionDownload($id)
    {
        $file = StudentFileResource::findOne($id);

        if (is_null($file)) {
            throw new NotFoundHttpException(Yii::t('app', 'Student File not found.'));
        }

        PermissionHelpers::isItMyStudentFile($file);

        Yii::$app->response->sendFile($file->path, basename($file->path));
    }

    /**
     * Upload a new student file
     * @return StudentFileResource|array
     * @throws BadRequestHttpException
     * @throws ForbiddenHttpException
     * @throws ServerErrorHttpException
     * @throws \Cz\Git\GitException
     *
     * @OA\Post(
     *     path="/student/student-files/upload",
     *     operationId="student::StudentFilesController::actionUpload",
     *     tags={"Student Student Files"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(ref="#/components/parameters/yii2_fields"),
     *     @OA\Parameter(ref="#/components/parameters/yii2_expand"),
     *     @OA\RequestBody(
     *         description="file to upload and taskID",
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(ref="#/components/schemas/Student_StudentFileUploadResource_ScenarioDefault"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="file uploaded",
     *         @OA\JsonContent(ref="#/components/schemas/Student_StudentFileResource_Read"),
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
        $model = new StudentFileUploadResource();
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
        if ($task->group->isCanvasCourse) {
            throw new BadRequestHttpException(
                Yii::t('app', 'This operation cannot be performed on a canvas synchronized course!')
            );
        }

        // Get previous file
        $prevStudentFile = StudentFileResource::findOne(['uploaderID' => Yii::$app->user->id, 'taskID' => $task->id]);

        // Verify that the task is open for submissions or the student has a special late submission permission.
        if (strtotime($task->hardDeadline) < time() && (is_null(
                    $prevStudentFile
                ) || $prevStudentFile->isAccepted !== StudentFile::IS_ACCEPTED_LATE_SUBMISSION)) {
            throw new BadRequestHttpException(Yii::t('app', 'The hard deadline of the solution has passed!'));
        }

        // Verify that the student has no accepted solution yet.
        if (!is_null($prevStudentFile) && $prevStudentFile->isAccepted === StudentFile::IS_ACCEPTED_ACCEPTED) {
            throw new BadRequestHttpException(Yii::t('app', 'Your solution was accepted!'));
        }

        return $this->saveFile(
            $prevStudentFile,
            $model->file,
            $task->id,
            Yii::$app->params['versionControl']['enabled'] && $task->isVersionControlled
        );
    }

    /**
     * Save the files to the disk (and to the git repository)
     * @param StudentFileResource|mixed $prevStudentFile
     * @param UploadedFile $newFile
     * @param int $taskID
     * @return StudentFileResource
     * @throws ServerErrorHttpException
     * @throws \Cz\Git\GitException
     */
    private function saveFile($prevStudentFile, $newFile, $taskID, $versionControlled)
    {
        // Set basepath
        $basepath = Yii::$app->basePath . '/' . Yii::$app->params['data_dir']
            . '/uploadedfiles/' . $taskID . '/' . strtolower(Yii::$app->user->identity->neptun) . '/';

        if (!file_exists($basepath)) {
            mkdir($basepath, 0755, true);
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

        $uploader = User::findOne(Yii::$app->user->id);
        $studentFile = null;
        if ($prevStudentFile == null) {
            $studentFile = new StudentFileResource();
            //Set details
            $studentFile->taskID = $taskID;
            $studentFile->grade = null;
            $studentFile->notes = "";
            $studentFile->uploaderID = $uploader->id;
            $studentFile->isVersionControlled = $versionControlled ? 1 : 0;
            $studentFile->uploadCount = 1;
        } else {
            $studentFile = $prevStudentFile;
            $studentFile->uploadCount++;
        }

        $studentFile->name = basename($newFile->name);
        $studentFile->uploadTime = date('Y-m-d H:i:s');
        $studentFile->isAccepted = StudentFile::IS_ACCEPTED_UPLOADED;
        $studentFile->verified = !$studentFile->task->passwordProtected;
        $studentFile->autoTesterStatus = StudentFile::AUTO_TESTER_STATUS_NOT_TESTED;
        $studentFile->codeCheckerResultID = null;

        if ($studentFile->save()) {
            Yii::info(
                "A new solution has been uploaded for " .
                "{$studentFile->task->name} ($taskID)",
                __METHOD__
            );
            return $studentFile;
        } else {
            throw new ServerErrorHttpException(Yii::t('app', "A database error occurred"));
        }
    }

    /**
     *
     * @OA\Post(
     *     path="/student/student-files/verify",
     *     operationId="student::StudentFilesController::actionVerify",
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
     *         @OA\JsonContent(ref="#/components/schemas/Student_StudentFileResource_Read"),
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

        $file = StudentFileResource::findOne($verifyResource->id);

        if (is_null($file)) {
            throw new NotFoundHttpException(Yii::t('app', 'Student File not found.'));
        }

        PermissionHelpers::isItMyStudentFile($file);

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
}
