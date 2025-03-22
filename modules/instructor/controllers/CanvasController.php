<?php

namespace app\modules\instructor\controllers;

use app\components\CanvasIntegration;
use app\models\AccessToken;
use app\models\Group;
use app\models\User;
use app\modules\instructor\resources\CanvasCourseResource;
use app\modules\instructor\resources\CanvasSectionResource;
use app\modules\instructor\resources\CanvasSetupResource;
use app\modules\instructor\resources\OAuth2ResponseResource;
use Yii;
use yii\base\Action;
use yii\data\ArrayDataProvider;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;
use yii\httpclient\Client;
use yii\web\BadRequestHttpException;
use yii\web\ConflictHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;

/**
 * This class controls the canvas integration related actions.
 */
class CanvasController extends BaseInstructorRestController
{
    private CanvasIntegration $canvas;

    /**
     * @param Action $action
     * @return bool
     * @throws BadRequestHttpException
     */
    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        if (!Yii::$app->params['canvas']['enabled']) {
            throw new BadRequestHttpException(Yii::t('app', 'Canvas synchronization is disabled! Contact the administrator for more information.'));
        }

        $this->canvas = new CanvasIntegration();

        return true;
    }

    /**
     * @return array
     */
    protected function verbs()
    {
        return ArrayHelper::merge(
            parent::verbs(),
            [
                'oauth2-response' => ['POST'],
                'setup' => ['POST'],
                'sync' => ['POST'],
                'cancel-sync' => ['POST'],
                'courses' => ['GET'],
                'sections' => ['GET'],
            ]
        );
    }

    /**
     * Get the OAuth2 token and refresh token from Canvas and save to the database
     * @return array|void
     * @throws ServerErrorHttpException
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\httpclient\Exception
     *
     * @OA\Post(
     *     path="/instructor/canvas/oauth2-response",
     *     operationId="instructor::CanvasController::actionOauth2Response",
     *     tags={"Instructor Canvas"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         description="OAuth2 response",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(ref="#/components/schemas/Instructor_OAuth2ResponseResource_ScenarioDefault"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="successful operation",
     *     ),
     *    @OA\Response(response=400, ref="#/components/responses/400"),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=422, ref="#/components/responses/422"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * )
     */
    public function actionOauth2Response()
    {
        // Load and validate OAuth2 response
        $model = new OAuth2ResponseResource();
        $model->load(Yii::$app->request->post(), '');

        if (!$model->validate()) {
            $this->response->statusCode = 422;
            return $model->errors;
        }

        // Validate OAuth2 state
        $currentToken = AccessToken::getCurrent();
        if (
            empty($model->state) || empty($currentToken->canvasOAuth2State)
            || $model->state !== $currentToken->canvasOAuth2State
        ) {
            throw new ServerErrorHttpException(Yii::t('app', 'Canvas login failed'));
        }

        if ($model->error === null) {
            $client = new Client(['baseUrl' => Yii::$app->params['canvas']['url']]);
            $response = $client->createRequest()
                ->setMethod('POST')
                ->setUrl('login/oauth2/token')
                ->setData(['grant_type' => 'authorization_code',
                    'client_id' => Yii::$app->params['canvas']['clientID'],
                    'client_secret' => Yii::$app->params['canvas']['secretKey'],
                    'redirect_uri' => Yii::$app->params['canvas']['redirectUri'],
                    'code' => $model->code,
                    'replace_tokens' => 1])
                ->send();
            if ($response->isOk) {
                $responseJson = Json::decode($response->content);
                $user = User::findOne(Yii::$app->user->id);
                $user->canvasID = $responseJson['user']['id'];
                $user->canvasToken = $responseJson['access_token'];
                $user->refreshToken = $responseJson['refresh_token'];
                $user->save();
                $this->response->statusCode = 204;

                Yii::info(
                    "Successful canvas login (canvas userID: $user->canvasID)",
                    __METHOD__
                );
            } else {
                throw new ServerErrorHttpException(Yii::t('app', 'Canvas login failed'));
            }
        } else {
            throw new ServerErrorHttpException(Yii::t('app', 'Canvas login failed'));
        }
    }

    /**
     * Set Canvas course and section fields before synchronization, then synchronizes course
     * @param int $groupID the id of the selected group
     * @return array|void
     * @throws BadRequestHttpException
     * @throws ConflictHttpException
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     * @throws ServerErrorHttpException
     * @OA\Post(
     *     path="/instructor/canvas/setup",
     *     operationId="instructor::CanvasController::actionSetup",
     *     tags={"Instructor Canvas"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="groupID",
     *         in="query",
     *         required=true,
     *         description="ID of the group",
     *         explode=true,
     *         @OA\Schema(ref="#/components/schemas/int_id")
     *     ),
     *     @OA\RequestBody(
     *         description="Canvas course settings",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(ref="#/components/schemas/Instructor_CanvasSetupResource_ScenarioDefault"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="successful operation",
     *     ),
     *    @OA\Response(response=400, ref="#/components/responses/400"),
     *    @OA\Response(
     *        response=401,
     *        description="Unauthorized: missing, invalid or expired access or Canvas token. If Canvas login is required, the Proxy-Authenticate header containains the login URL.",
     *        @OA\JsonContent(ref="#/components/schemas/Yii2Error"),
     *    ),
     *    @OA\Response(response=403, ref="#/components/responses/403"),
     *    @OA\Response(response=404, ref="#/components/responses/404"),
     *    @OA\Response(response=409, ref="#/components/responses/409"),
     *    @OA\Response(response=422, ref="#/components/responses/422"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * )
     */
    public function actionSetup(int $groupID)
    {
        $group = Group::findOne($groupID);

        if (is_null($group)) {
            throw new NotFoundHttpException(Yii::t('app', 'Group not found.'));
        }

        // Authorization check
        if (!Yii::$app->user->can('manageGroup', ['groupID' => $groupID])) {
            throw new ForbiddenHttpException(Yii::t('app', 'You must be an instructor of the group to perform this action!'));
        }

        if ($group->isCanvasCourse) {
            throw new ConflictHttpException(Yii::t('app', 'Group is already synchronized.'));
        }

        if (count($group->tasks) > 0 || count($group->subscriptions) > 0) {
            throw new BadRequestHttpException(Yii::t('app', 'The synchronization is disabled if a task or user is assigned to the group!'));
        }

        $user = User::findIdentity(Yii::$app->user->id);
        if (!$user->isAuthenticatedInCanvas) {
            $this->response->statusCode = 401;
            $this->response->headers->add('Proxy-Authenticate', CanvasIntegration::getLoginURL());
            return;
        }

        if (!$this->canvas->refreshCanvasToken($user)) {
            throw new ServerErrorHttpException(Yii::t('app', 'Failed to refresh Canvas Token.'));
        }

        $canvasCourse = new CanvasSetupResource();
        $canvasCourse->load(Yii::$app->request->post(), '');
        if (!$canvasCourse->validate()) {
            $this->response->statusCode = 422;
            return $canvasCourse->errors;
        }

        $group = $this->canvas->saveCanvasGroup(
            $groupID,
            $canvasCourse->canvasSection,
            $canvasCourse->canvasCourse,
            $canvasCourse->syncLevel
        );

        $this->canvas->synchronizeGroupData($group);
        $this->response->statusCode = 204;
    }

    /**
     * Synchronize the selected course and group with Canvas
     * @param int $groupID the id of the selected group
     * @throws ConflictHttpException
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     * @throws ServerErrorHttpException
     *
     * @OA\Post(
     *     path="/instructor/canvas/sync",
     *     operationId="instructor::CanvasController::actionSync",
     *     tags={"Instructor Canvas"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="groupID",
     *         in="query",
     *         required=true,
     *         description="ID of the group",
     *         explode=true,
     *         @OA\Schema(ref="#/components/schemas/int_id")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="successful operation",
     *     ),
     *    @OA\Response(response=400, ref="#/components/responses/400"),
     *    @OA\Response(
     *        response=401,
     *        description="Unauthorized: missing, invalid or expired access or Canvas token. If Canvas login is required, the Proxy-Authenticate header containains the login URL.",
     *        @OA\JsonContent(ref="#/components/schemas/Yii2Error"),
     *    ),
     *    @OA\Response(response=403, ref="#/components/responses/403"),
     *    @OA\Response(response=404, ref="#/components/responses/404"),
     *    @OA\Response(response=409, ref="#/components/responses/409"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * )
     */
    public function actionSync(int $groupID): void
    {
        $group = Group::findOne($groupID);

        if (is_null($group)) {
            throw new NotFoundHttpException(Yii::t('app', 'Group not found.'));
        }

        // Authorization check
        if (!Yii::$app->user->can('manageGroup', ['groupID' => $groupID])) {
            throw new ForbiddenHttpException(Yii::t('app', 'You must be an instructor of the group to perform this action!'));
        }

        // Setup is required before synchronization
        if (!$group->isCanvasCourse) {
            throw new ConflictHttpException(Yii::t('app', 'Synchronization is not configured for this group.'));
        }

        // Use original synchronizer when syncing group manually, if it is possible
        if (empty($group->synchronizerID)) {
            $user = User::findIdentity(Yii::$app->user->id);
            if (!$user->isAuthenticatedInCanvas) {
                $this->response->statusCode = 401;
                $this->response->headers->add('Proxy-Authenticate', CanvasIntegration::getLoginURL());
                return;
            }
        } else {
            $user = User::findIdentity($group->synchronizerID);
        }

        if (!$this->canvas->refreshCanvasToken($user)) {
            throw new ServerErrorHttpException(Yii::t('app', 'Failed to refresh Canvas Token.'));
        }

        $this->canvas->synchronizeGroupData($group);
        $this->response->statusCode = 204;
    }

    /**
     * Synchronize the selected course and group with Canvas
     * @param int $groupID the id of the selected group
     * @throws ConflictHttpException
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     *
     * @OA\Post(
     *     path="/instructor/canvas/cancel-sync",
     *     operationId="instructor::CanvasController::actionCancelSync",
     *     tags={"Instructor Canvas"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="groupID",
     *         in="query",
     *         required=true,
     *         description="ID of the group",
     *         explode=true,
     *         @OA\Schema(ref="#/components/schemas/int_id")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="successful operation",
     *     ),
     *    @OA\Response(response=400, ref="#/components/responses/400"),
     *    @OA\Response(
     *        response=401,
     *        description="Unauthorized: missing, invalid or expired access or Canvas token. If Canvas login is required, the Proxy-Authenticate header containains the login URL.",
     *        @OA\JsonContent(ref="#/components/schemas/Yii2Error"),
     *    ),
     *    @OA\Response(response=403, ref="#/components/responses/403"),
     *    @OA\Response(response=404, ref="#/components/responses/404"),
     *    @OA\Response(response=409, ref="#/components/responses/409"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * )
     */
    public function actionCancelSync(int $groupID): void
    {
        $group = Group::findOne($groupID);

        if (is_null($group)) {
            throw new NotFoundHttpException(Yii::t('app', 'Group not found.'));
        }

        // Authorization check
        if (!Yii::$app->user->can('manageGroup', ['groupID' => $groupID])) {
            throw new ForbiddenHttpException(Yii::t('app', 'You must be an instructor of the group to perform this action!'));
        }

        // Setup is required before synchronization
        if (!$group->isCanvasCourse) {
            throw new ConflictHttpException(Yii::t('app', 'Synchronization is not configured for this group.'));
        }

        $this->canvas->cancelCanvasSync($group);
        $this->response->statusCode = 204;
    }

    /**
     * Lists available Canvas courses
     * @return ArrayDataProvider|void
     *
     * @OA\Get(
     *     path="/instructor/canvas/courses",
     *     operationId="instructor::CanvasController::actionCourses",
     *     tags={"Instructor Canvas"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(ref="#/components/parameters/yii2_fields"),
     *     @OA\Parameter(ref="#/components/parameters/yii2_expand"),
     *     @OA\Parameter(ref="#/components/parameters/yii2_sort"),
     *
     *     @OA\Response(
     *        response=200,
     *        description="successful operation",
     *        @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Instructor_CanvasCourseResource_Read")),
     *    ),
     *    @OA\Response(response=400, ref="#/components/responses/400"),
     *    @OA\Response(
     *        response=401,
     *        description="Unauthorized: missing, invalid or expired access or Canvas token. If Canvas login is required, the Proxy-Authenticate header containains the login URL.",
     *        @OA\JsonContent(ref="#/components/schemas/Yii2Error"),
     *    ),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * )
     */
    public function actionCourses()
    {
        $user = User::findIdentity(Yii::$app->user->id);
        if (!$user->isAuthenticatedInCanvas) {
            $this->response->statusCode = 401;
            $this->response->headers->add('Proxy-Authenticate', CanvasIntegration::getLoginURL());
            return;
        }

        if (!$this->canvas->refreshCanvasToken($user)) {
            throw new ServerErrorHttpException(Yii::t('app', 'Failed to refresh Canvas Token.'));
        }

        $courses = $this->canvas->findCanvasCourses();
        $out = [];

        foreach ($courses as $course) {
            $resource = new CanvasCourseResource();
            $resource->id = $course['id'];
            $resource->name = $course['name'];
            $out[] = $resource;
        }

        return  new ArrayDataProvider(
            [
                'modelClass' => CanvasCourseResource::class,
                'allModels' => $out,
                'pagination' => false,
            ]
        );
    }

    /**
     * Get the sections for the selected Canvas course
     * @param int $courseID Canvas Course ID
     * @return ArrayDataProvider|void
     *
     * @OA\Get(
     *     path="/instructor/canvas/sections",
     *     operationId="instructor::CanvasController::actionSections",
     *     tags={"Instructor Canvas"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(ref="#/components/parameters/yii2_fields"),
     *     @OA\Parameter(ref="#/components/parameters/yii2_expand"),
     *     @OA\Parameter(ref="#/components/parameters/yii2_sort"),
     *     @OA\Parameter(
     *         name="courseID",
     *         in="query",
     *         required=true,
     *         description="ID of the Canvas course",
     *         explode=true,
     *         @OA\Schema(ref="#/components/schemas/int_id")
     *     ),
     *     @OA\Response(
     *        response=200,
     *        description="successful operation",
     *        @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Instructor_CanvasSectionResource_Read")),
     *    ),
     *    @OA\Response(response=400, ref="#/components/responses/400"),
     *    @OA\Response(
     *        response=401,
     *        description="Unauthorized: missing, invalid or expired access or Canvas token. If Canvas login is required, the Proxy-Authenticate header containains the login URL.",
     *        @OA\JsonContent(ref="#/components/schemas/Yii2Error"),
     *    ),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * )
     */
    public function actionSections(int $courseID)
    {
        $user = User::findIdentity(Yii::$app->user->id);
        if (!$user->isAuthenticatedInCanvas) {
            $this->response->statusCode = 401;
            $this->response->headers->add('Proxy-Authenticate', CanvasIntegration::getLoginURL());
            return;
        }

        if (!$this->canvas->refreshCanvasToken($user)) {
            throw new ServerErrorHttpException(Yii::t('app', 'Failed to refresh Canvas Token.'));
        }

        $sections = $this->canvas->findCanvasSections($courseID);
        $out = [];

        $out[0] = new CanvasSectionResource();
        $out[0]->id = '-1';
        $out[0]->name = Yii::t('app', 'All sections');

        foreach ($sections as $section) {
            $resource = new CanvasSectionResource();
            $resource->id = $section['id'];
            $resource->name = $section['name'];
            $resource->totalStudents = $section['total_students'];
            $out[] = $resource;
        }

        return  new ArrayDataProvider(
            [
                'modelClass' => CanvasSectionResource::class,
                'allModels' => $out,
                'pagination' => false,
            ]
        );
    }
}
