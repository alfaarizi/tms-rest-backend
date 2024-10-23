<?php

namespace app\modules\instructor\controllers;

use app\components\docker\DockerImageManager;
use app\models\Submission;
use app\models\Task;
use app\modules\instructor\resources\EvaluatorTemplateResource;
use app\modules\instructor\resources\SetupAutoTesterResource;
use app\modules\instructor\resources\SetupCodeCheckerResource;
use app\modules\instructor\resources\SetupEvaluatorEnvironmentResource;
use app\modules\instructor\resources\StaticAnalyzerToolResource;
use app\modules\instructor\resources\TaskResource;
use app\modules\instructor\resources\EvaluatorAdditionalInformationResource;
use app\resources\SemesterResource;
use yii\base\ErrorException;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;
use yii\web\UploadedFile;
use Yii;

class EvaluatorController extends BaseInstructorRestController
{
    protected function verbs(): array
    {
        return ArrayHelper::merge(
            parent::verbs(),
            [
                'tester-form-data' => ['GET'],
                'setup-environment' => ['POST'],
                'setup-auto-tester' => ['POST'],
                'setup-code-checker' => ['POST'],
                'update-docker-image' => ['POST'],
            ]
        );
    }

    /**
     * @param $action
     * @return bool
     * @throws BadRequestHttpException
     */
    public function beforeAction($action): bool
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        if (!Yii::$app->params['evaluator']['enabled']) {
            throw new BadRequestHttpException(
                Yii::t('app', 'Evaluator is disabled. Contact the administrator for more information.' )
            );
        }

        return true;
    }

    /**
     * Updates evaluator environment for a task
     *
     * @param int $id the id of the task
     * @return TaskResource|array
     * @throws BadRequestHttpException
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     * @throws ServerErrorHttpException
     * @throws ErrorException
     *
     * @OA\Post(
     *     path="/instructor/tasks/{id}/evaluator/setup-environment",
     *     operationId="instructor::EvaluatorController::actionSetupEnvironment",
     *     tags={"Instructor Tasks Evaluator"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *          name="id",
     *          in="path",
     *          required=true,
     *          description="ID of the task",
     *          @OA\Schema(ref="#/components/schemas/int_id")
     *     ),
     *     @OA\Parameter(ref="#/components/parameters/yii2_fields"),
     *     @OA\Parameter(ref="#/components/parameters/yii2_expand"),
     *     @OA\RequestBody(
     *         description="environment setup",
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(ref="#/components/schemas/Instructor_SetupEvaluatorEnvironmentResource_ScenarioDefault"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="updated task",
     *         @OA\JsonContent(ref="#/components/schemas/Instructor_TaskResource_Read"),
     *     ),
     *    @OA\Response(response=400, ref="#/components/responses/400"),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=403, ref="#/components/responses/403"),
     *    @OA\Response(response=404, ref="#/components/responses/404"),
     *    @OA\Response(response=422, ref="#/components/responses/422"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * )
     */
    public function actionSetupEnvironment(int $id)
    {
        $task = $this->getTaskWithAuthorizationCheck($id);

        // Check semester
        if ($task->semesterID !== SemesterResource::getActualID()) {
            throw new BadRequestHttpException(
                Yii::t('app', "You can't modify a task from a previous semester!")
            );
        }

        $setupData = new SetupEvaluatorEnvironmentResource();
        $setupData->files = UploadedFile::getInstancesByName('files');
        $setupData->load(Yii::$app->request->post(), '');

        if (!$setupData->validate()) {
            $this->response->statusCode = 422;
            return $setupData->errors;
        }

        $task->testOS = $setupData->testOS;
        $task->imageName = $setupData->imageName;

        if (!$task->validate()) {
            $this->response->statusCode = 422;
            return $task->errors;
        }

        $sourcedir = Yii::getAlias("@tmp/instructor/") . $task->groupID . '/' . $task->id . '/autotest/';

        // Create tmp dir for the given groupID and taskID
        if (file_exists($sourcedir)) {
            FileHelper::removeDirectory($sourcedir);
        }
        FileHelper::createDirectory($sourcedir, 0755, true);

        if ($setupData->files) {
            foreach ($setupData->files as $file) {
                if (!$file->saveAs($sourcedir . $file->name)) {
                    // Log
                    Yii::error(
                        "Failed to save file to the disc ($file->name), error code: $file->error",
                        __METHOD__
                    );
                    throw new ServerErrorHttpException(
                        Yii::t("app", "Failed to save file. Error logged.") . " ($file->name)"
                    );
                }
            }
        }

        $dockerImageManager = Yii::$container->get(DockerImageManager::class, ['os' => $task->testOS]);
        if (file_exists($sourcedir . 'Dockerfile')) {
            if ($dockerImageManager->alreadyBuilt($task->localImageName)) {
                $dockerImageManager->removeImage($task->localImageName);
            }

            $buildResult = $dockerImageManager->buildImageForTask($task->localImageName, $sourcedir);

            if (!$buildResult['success']) {
                $error = $buildResult['log'] . PHP_EOL . $buildResult['error'];
                throw new ServerErrorHttpException($error);
            } else {
                $task->imageName = $task->localImageName;
            }
        }

        if (!$task->isLocalImage && !$dockerImageManager->alreadyBuilt($task->imageName)) {
            $dockerImageManager->pullImage($task->imageName);
        }

        // Clean temp files
        if (file_exists($sourcedir)) {
            FileHelper::removeDirectory($sourcedir);
        }

        if ($task->save(false)) {
            return $task;
        } else {
            throw new ServerErrorHttpException(Yii::t('app', 'A database error occurred'));
        }
    }

    /**
     * Updates auto tester for a task
     *
     * @param int $id the id of the task
     * @return TaskResource|array
     * @throws BadRequestHttpException
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     * @throws ServerErrorHttpException
     * @throws ErrorException
     *
     * @OA\Post(
     *     path="/instructor/tasks/{id}/evaluator/setup-auto-tester",
     *     operationId="instructor::EvaluatorController::actionSetupAutoTester",
     *     tags={"Instructor Tasks Evaluator"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *          name="id",
     *          in="path",
     *          required=true,
     *          description="ID of the task",
     *          @OA\Schema(ref="#/components/schemas/int_id")
     *     ),
     *     @OA\Parameter(ref="#/components/parameters/yii2_fields"),
     *     @OA\Parameter(ref="#/components/parameters/yii2_expand"),
     *     @OA\RequestBody(
     *         description="tester data",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(ref="#/components/schemas/Instructor_SetupAutoTesterResource_ScenarioDefault"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="updated task",
     *         @OA\JsonContent(ref="#/components/schemas/Instructor_TaskResource_Read"),
     *     ),
     *    @OA\Response(response=400, ref="#/components/responses/400"),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=403, ref="#/components/responses/403"),
     *    @OA\Response(response=404, ref="#/components/responses/404"),
     *    @OA\Response(response=422, ref="#/components/responses/422"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * )
     */
    public function actionSetupAutoTester(int $id)
    {
        $task = $this->getTaskWithAuthorizationCheck($id);

        // Check semester
        if ($task->semesterID !== SemesterResource::getActualID()) {
            throw new BadRequestHttpException(
                Yii::t('app', "You can't modify a task from a previous semester!")
            );
        }

        $setupData = new SetupAutoTesterResource();
        $setupData->load(Yii::$app->request->post(), '');

        // Check platform support
        if ($setupData->appType == Task::APP_TYPE_WEB &&
            empty(Yii::$app->params['evaluator']['webApp'][$task->testOS]['reservedPorts'])) {
            throw new BadRequestHttpException(
                Yii::t('app', 'Platform not supported for web application testing.')
            );
        }

        if (!$setupData->validate()) {
            $this->response->statusCode = 422;
            return $setupData->errors;
        }

        $task->autoTest = $setupData->autoTest;
        $task->compileInstructions = $setupData->compileInstructions;
        $task->runInstructions = $setupData->runInstructions;
        $task->showFullErrorMsg = $setupData->showFullErrorMsg;
        $task->appType = $setupData->appType;
        $task->port = $setupData->port;

        if (!$task->validate()) {
            $this->response->statusCode = 422;
            return $task->errors;
        }

        if ($task->save(false)) {
            if ($setupData->reevaluateAutoTest) {
                Submission::updateAll(
                    [
                        'status' => Submission::STATUS_UPLOADED,
                        'autoTesterStatus' => Submission::AUTO_TESTER_STATUS_NOT_TESTED,
                    ],
                    [
                        'and',
                        ['in', 'status', [Submission::STATUS_PASSED, Submission::STATUS_FAILED]],
                        ['=', 'taskID', $task->id],
                    ]
                );
            }
            return $task;
        } else {
            throw new ServerErrorHttpException(Yii::t('app', 'A database error occurred'));
        }
    }

    /**
     * Provides additional information needed for environment setup
     * @param int $id
     * @return EvaluatorAdditionalInformationResource
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     *
     * @OA\Get(
     *     path="/instructor/tasks/{id}/evaluator/additional-information",
     *     operationId="instructor::EvaluatorController::actionAdditionalInformation",
     *     tags={"Instructor Tasks Evaluator"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *          name="id",
     *          in="path",
     *          required=true,
     *          description="ID of the task",
     *          @OA\Schema(ref="#/components/schemas/int_id")
     *     ),
     *     @OA\Parameter(ref="#/components/parameters/yii2_fields"),
     *     @OA\Parameter(ref="#/components/parameters/yii2_expand"),
     *     @OA\Response(
     *         response=200,
     *         description="updated task",
     *         @OA\JsonContent(ref="#/components/schemas/Instructor_EvaluatorAdditionalInformationResource_Read"),
     *     ),
     *     @OA\Response(response=400, ref="#/components/responses/400"),
     *     @OA\Response(response=401, ref="#/components/responses/401"),
     *     @OA\Response(response=403, ref="#/components/responses/403"),
     *     @OA\Response(response=404, ref="#/components/responses/404"),
     *     @OA\Response(response=500, ref="#/components/responses/500"),
     * )
     */
    public function actionAdditionalInformation(int $id): EvaluatorAdditionalInformationResource
    {
        $task = $this->getTaskWithAuthorizationCheck($id);
        return $this->createAdditionalInformationResponse($task);
    }

    /**
     * Downloads the latest version of the configured Docker image for the given task
     * @param int $id
     * @return EvaluatorAdditionalInformationResource
     * @throws BadRequestHttpException
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     *
     * @OA\Post (
     *     path="/instructor/tasks/{id}/update-docker-image",
     *     operationId="instructor::EvaluatorController::udapteDockerImage",
     *     tags={"Instructor Tasks Evaluator"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *          name="id",
     *          in="path",
     *          required=true,
     *          description="ID of the task",
     *          @OA\Schema(ref="#/components/schemas/int_id")
     *     ),
     *     @OA\Parameter(ref="#/components/parameters/yii2_fields"),
     *     @OA\Parameter(ref="#/components/parameters/yii2_expand"),
     *     @OA\Response(
     *         response=200,
     *         description="updated task",
     *         @OA\JsonContent(ref="#/components/schemas/Instructor_EvaluatorAdditionalInformationResource_Read"),
     *     ),
     *     @OA\Response(response=400, ref="#/components/responses/400"),
     *     @OA\Response(response=401, ref="#/components/responses/401"),
     *     @OA\Response(response=403, ref="#/components/responses/403"),
     *     @OA\Response(response=404, ref="#/components/responses/404"),
     *     @OA\Response(response=500, ref="#/components/responses/500"),
     * )
     */
    public function actionUpdateDockerImage(int $id): EvaluatorAdditionalInformationResource
    {
        $task = $this->getTaskWithAuthorizationCheck($id);
        $dockerImageManager = Yii::$container->get(DockerImageManager::class, ['os' => $task->testOS]);
        if (!$task->isLocalImage) {
            $dockerImageManager->pullImage($task->imageName);
        } else {
            throw new BadRequestHttpException(Yii::t('app', 'Local Docker images can\'t be updated from registry.'));
        }

        return $this->createAdditionalInformationResponse($task);
    }

    /**
     * @param int $id
     * @return TaskResource
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     */
    private function getTaskWithAuthorizationCheck(int $id): TaskResource
    {
        $task = TaskResource::findOne($id);

        if (is_null($task)) {
            throw new NotFoundHttpException(Yii::t('app', 'Task not found.'));
        }

        // Authorization check
        if (!Yii::$app->user->can('manageGroup', ['groupID' => $task->groupID])) {
            throw new ForbiddenHttpException(
                Yii::t('app', 'You must be an instructor of the group to perform this action!')
            );
        }
        return $task;
    }


    /**
     * Updates auto tester for a task
     *
     * @param int $id the id of the task
     * @return TaskResource|array
     * @throws BadRequestHttpException
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     * @throws ServerErrorHttpException
     * @throws ErrorException
     *
     * @OA\Post(
     *     path="/instructor/tasks/{id}/evaluator/setup-code-checker",
     *     operationId="instructor::EvaluatorController::actionCodeChecker",
     *     tags={"Instructor Tasks Evaluator"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *          name="id",
     *          in="path",
     *          required=true,
     *          description="ID of the task",
     *          @OA\Schema(ref="#/components/schemas/int_id")
     *     ),
     *     @OA\Parameter(ref="#/components/parameters/yii2_fields"),
     *     @OA\Parameter(ref="#/components/parameters/yii2_expand"),
     *     @OA\RequestBody(
     *         description="tester data",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(ref="#/components/schemas/Instructor_SetupCodeCheckerResource_ScenarioDefault"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="updated task",
     *         @OA\JsonContent(ref="#/components/schemas/Instructor_TaskResource_Read"),
     *     ),
     *    @OA\Response(response=400, ref="#/components/responses/400"),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=403, ref="#/components/responses/403"),
     *    @OA\Response(response=404, ref="#/components/responses/404"),
     *    @OA\Response(response=422, ref="#/components/responses/422"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * )
     */
    public function actionSetupCodeChecker(int $id)
    {
        $task = $this->getTaskWithAuthorizationCheck($id);

        // Check semester
        if ($task->semesterID !== SemesterResource::getActualID()) {
            throw new BadRequestHttpException(
                Yii::t('app', "You can't modify a task from a previous semester!")
            );
        }

        $setupData = new SetupCodeCheckerResource();
        $setupData->load(Yii::$app->request->post(), '');

        if (!$setupData->validate()) {
            $this->response->statusCode = 422;
            return $setupData->errors;
        }

        $task->staticCodeAnalysis = $setupData->staticCodeAnalysis;
        $task->staticCodeAnalyzerTool = $setupData->staticCodeAnalyzerTool;
        $task->codeCheckerSkipFile = $setupData->codeCheckerSkipFile;
        if ($setupData->staticCodeAnalyzerTool === 'codechecker') {
            $task->codeCheckerCompileInstructions = $setupData->codeCheckerCompileInstructions;
            $task->codeCheckerToggles = $setupData->codeCheckerToggles;
        } else {
            $task->staticCodeAnalyzerInstructions = $setupData->staticCodeAnalyzerInstructions;
        }

        if (!$task->validate()) {
            $this->response->statusCode = 422;
            return $task->errors;
        }

        if ($task->save(false)) {

            if ($setupData->reevaluateStaticCodeAnalysis){
                Submission::updateAll(
                    [
                        'codeCheckerResultID' => null,
                    ],
                    [
                        'and',
                        ['not in', 'status', [Submission::STATUS_ACCEPTED, Submission::STATUS_REJECTED]],
                        ['=', 'taskID', $task->id],
                    ]
                );
            }

            return $task;
        } else {
            throw new ServerErrorHttpException(Yii::t('app', 'A database error occurred'));
        }
    }

    /**
     * @param TaskResource $task
     * @return EvaluatorAdditionalInformationResource
     */
    private function createAdditionalInformationResponse(TaskResource $task): EvaluatorAdditionalInformationResource
    {
        $dockerImageManager = Yii::$container->get(DockerImageManager::class, ['os' => $task->testOS]);
        $templates = [];
        $osMap = $task->testOSMap();

        foreach (Yii::$app->params['evaluator']['templates'] as $key => $template) {
            if (in_array($template['os'], array_keys($osMap))) {
                $resource = new EvaluatorTemplateResource();
                $resource->name = $template['name'];
                $resource->os = $template['os'];
                $resource->image = $template['image'];

                $resource->autoTest = $template['autoTest'];
                if ($resource->autoTest) {
                    $resource->appType = $template['appType'];
                    $resource->compileInstructions = $template['compileInstructions'];
                    $resource->runInstructions = $template['runInstructions'];
                }

                $resource->staticCodeAnalysis = $template['staticCodeAnalysis'];
                if ($resource->staticCodeAnalysis) {
                    $resource->staticCodeAnalyzerTool = $template['staticCodeAnalyzerTool'];
                    $resource->codeCheckerSkipFile = $template['codeCheckerSkipFile'];

                    if ($resource->staticCodeAnalyzerTool === 'codechecker') {
                        $resource->codeCheckerCompileInstructions = $template['codeCheckerCompileInstructions'];
                        $resource->codeCheckerToggles = $template['codeCheckerToggles'];
                    } else {
                        $resource->staticCodeAnalyzerInstructions = $template['staticCodeAnalyzerInstructions'];
                    }
                }

                $templates[] = $resource;
            }
        }

        $response = new EvaluatorAdditionalInformationResource();
        $response->templates = $templates;
        $response->osMap = $osMap;
        $response->appTypes = Task::APP_TYPES;
        $response->imageSuccessfullyBuilt = $dockerImageManager->alreadyBuilt($task->imageName);

        if ($response->imageSuccessfullyBuilt) {
            $response->imageCreationDate = $dockerImageManager->inspectImage($task->imageName)->getCreated();
        }
        $response->supportedStaticAnalyzers = [];
        foreach (Yii::$app->params["evaluator"]["supportedStaticAnalyzerTools"] as $key => $value) {
            $analyzerToolResource = new StaticAnalyzerToolResource();
            $analyzerToolResource->name = $key;
            $analyzerToolResource->title = $value['title'];
            $analyzerToolResource->outputPath = $value['outputPath'];
            $response->supportedStaticAnalyzers[] = $analyzerToolResource;
        }

        return $response;
    }
}
