<?php

namespace app\modules\instructor\controllers;

use app\components\RegexUtils;
use app\models\StudentFile;
use app\modules\instructor\components\exception\WebAppExecutionException;
use app\modules\instructor\components\WebAppExecutor;
use app\modules\instructor\resources\SetupWebAppExecutionResource;
use app\modules\instructor\resources\WebAppExecutionResource;
use Exception;
use Yii;
use yii\web\BadRequestHttpException;
use yii\web\ConflictHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;


/**
 * This class provides access to remote executions for instructors
 */
class WebAppExecutionController extends BaseInstructorRestController
{
    private WebAppExecutor $webAppExecutor;

    public function __construct($id, $module, ?WebAppExecutor $webAppExecutor = null, $config = [])
    {
        parent::__construct($id, $module, $config);
        if (empty($webAppExecutor)) {
            $this->webAppExecutor = new WebAppExecutor();
        } else {
            $this->webAppExecutor = $webAppExecutor;
        }
    }

    /**
     * @inheritdoc
     */
    protected function verbs()
    {
        return array_merge(
            parent::verbs(),
            [
                'index' => ['GET'],
                'create' => ['POST'],
                'delete' => ['DELETE'],
                'download-run-log' => ['GET'],
            ]
        );
    }

    /**
     * Get running execution of web application started by the current user
     *
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     *
     * @OA\Get(
     *     path="/instructor/web-app-execution",
     *     operationId="instructor::WebAppExecutionController::actionIndex",
     *     tags={"Instructor Web App Execution"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="studentFileID",
     *         in="query",
     *         required=true,
     *         description="ID of of the related Student File",
     *         @OA\Schema(ref="#/components/schemas/int_id"),
     *     ),
     *     @OA\Response(
     *        response=200,
     *        description="successful operation",
     *        @OA\JsonContent(ref="#/components/schemas/Instructor_WebAppExecutionResource_Read"),
     *    ),
     *    @OA\Response(response=400, ref="#/components/responses/400"),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=403, ref="#/components/responses/403"),
     *    @OA\Response(response=404, ref="#/components/responses/404"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     *  ),
     */
    public function actionIndex(int $studentFileID): ?WebAppExecutionResource
    {
        $studentFile = StudentFile::findOne($studentFileID);
        $this->validateGroupAccess($studentFile->task->groupID);

        if (is_null($studentFile)) {
            throw new NotFoundHttpException(Yii::t('app', 'Student file not found.'));
        }

        return WebAppExecutionResource::findOne(['studentFileID' => $studentFileID, 'instructorID' => Yii::$app->user->id]);
    }

    /**
     * Launch a new web application
     * @return WebAppExecutionResource|array
     * @throws ConflictHttpException
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     * @throws ServerErrorHttpException
     * @throws BadRequestHttpException
     *
     * @OA\Post(
     *     path="/instructor/web-app-execution",
     *     operationId="instructor::WebAppExecutionController::actionCreate",
     *     tags={"Instructor WebAppExecution"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(ref="#/components/parameters/yii2_fields"),
     *     @OA\Parameter(ref="#/components/parameters/yii2_expand"),
     *     @OA\RequestBody(
     *         description="WebAppExecution Form Data",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(ref="#/components/schemas/Instructor_SetupWebAppExecutionResource_ScenarioDefault"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="new task created",
     *         @OA\JsonContent(ref="#/components/schemas/Instructor_WebAppExecutionResource_Read"),
     *     ),
     *    @OA\Response(response=400, ref="#/components/responses/400"),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=409, ref="#/components/responses/409"),
     *    @OA\Response(response=403, ref="#/components/responses/403"),
     *    @OA\Response(response=422, ref="#/components/responses/422"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * )
     */
    public function actionCreate()
    {
        $setupData = new SetupWebAppExecutionResource();
        $setupData->load(Yii::$app->request->post(), '');

        if (!$setupData->validate()) {
            $this->response->statusCode = 422;
            return $setupData->errors;
        }

        $studentFile = StudentFile::findOne($setupData->studentFileID);

        if (is_null($studentFile)) {
            throw new NotFoundHttpException(Yii::t('app', 'Student file not found.'));
        }
        $this->validateGroupAccess($studentFile->task->groupID);

        /*
        if (!$this->webAppExecutionEnabledForOs($studentFile->task->testOS)) {
            throw new BadRequestHttpException(
                Yii::t('app', 'Web app execution not enabled for os: {os}',
                       ['os' => $studentFile->task->testOS])
            );
        }
        */

        try {
            return $this->webAppExecutor->startWebApplication($studentFile, Yii::$app->user->id, $setupData);
        } catch (WebAppExecutionException $e) {
            Yii::info(
                "Web app start for student file [$this->id] failed at stage: " . $e->getCode() . ', ' . $e->getMessage(),  __METHOD__);
            switch ($e->getCode()) {
                case WebAppExecutionException::$PREPARATION_FAILURE:
                    throw new ConflictHttpException($e->getMessageTranslated());
                case WebAppExecutionException::$START_UP_FAILURE:
                    throw new BadRequestHttpException($e->getMessageTranslated());
                default:
                    throw $e;
            }
        } catch (Exception $e) {
            Yii::error("Failed to start web application: " . $e->getMessage() . ' ' . $e->getTraceAsString(),  __METHOD__);
            throw new ServerErrorHttpException(Yii::t('app', 'Failed to start web application.'));
        }
    }

    /**
     * Shutdown Web App Execution
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     * @throws ServerErrorHttpException
     *
     * @OA\Delete(
     *     path="/instructor/web-app-execution/{id}",
     *     operationId="instructor::WebAppExecutionController::actionDelete",
     *     tags={"Instructor Web App Execution"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *        name="id",
     *        in="path",
     *        required=true,
     *        description="ID of the web app execution",
     *        @OA\Schema(ref="#/components/schemas/int_id"),
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="web app shut down",
     *     ),
     *    @OA\Response(response=400, ref="#/components/responses/400"),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=403, ref="#/components/responses/403"),
     *    @OA\Response(response=404, ref="#/components/responses/404"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * )
     */
    public function actionDelete(int $id): void
    {
        $webAppExecutionResource = WebAppExecutionResource::findOne(['id' => $id]);

        if (is_null($webAppExecutionResource)) {
            throw new NotFoundHttpException(Yii::t('app', 'Running web app not found.'));
        }

        $this->validateGroupAccess($webAppExecutionResource->studentFile->task->groupID);

        if (Yii::$app->user->id != $webAppExecutionResource->instructorID) {
            throw new ForbiddenHttpException(Yii::t('app','User not allowed to shut down this instance.'));
        }

        try{
            $this->webAppExecutor->stopWebApplication($webAppExecutionResource);
        } catch (Exception $e) {
            Yii::error("Failed to shutdown web app [" . $webAppExecutionResource->id . "]", __METHOD__);
            throw new ServerErrorHttpException(Yii::t('app', 'Failed to shut down web application.'));
        }
    }

    /**
     * Download the run log from the given web app execution instance
     * @throws \yii\base\InvalidConfigException
     *
     * @OA\Get(
     *     path="/instructor/web-app-execution/{id}/download-run-log",
     *     operationId="instructor::WebAppExecutionController::actionDownloadRunLog",
     *     tags={"Instructor Web App Execution"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *        name="id",
     *        in="path",
     *        required=true,
     *        description="ID of the web app execution",
     *        @OA\Schema(ref="#/components/schemas/int_id"),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="successful operation",
     *     ),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=404, ref="#/components/responses/404"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * ),
     */
    public function actionDownloadRunLog(int $id): void
    {
        $webAppExecutionResource = WebAppExecutionResource::findOne(['id' => $id]);

        if (is_null($webAppExecutionResource)) {
            throw new NotFoundHttpException(Yii::t('app', 'Running web app not found.'));
        }

        $this->validateGroupAccess($webAppExecutionResource->studentFile->task->groupID);

        if (Yii::$app->user->id != $webAppExecutionResource->instructorID) {
            throw new ForbiddenHttpException(Yii::t('app','User not allowed to shut down this instance.'));
        }

        $logs = $this->webAppExecutor->fetchRunLog($webAppExecutionResource);
        Yii::$app->response->sendContentAsFile($logs, 'run.log')->send();
    }

    /**
     * @throws ForbiddenHttpException
     */
    private function validateGroupAccess(int $groupID): void
    {
        if (!Yii::$app->user->can('manageGroup', ['groupID' => $groupID])) {
            throw new ForbiddenHttpException(
                Yii::t('app', 'You must be an instructor of the group to perform this action!')
            );
        }
    }

    public function webAppExecutionEnabledForOs(string $os): bool
    {
        if (Yii::$app->params['evaluator']['webApp']['gateway']['enabled']) {
            return true;
        }
        return WebAppExecutor::isDockerHostLocal($os);
    }
}
