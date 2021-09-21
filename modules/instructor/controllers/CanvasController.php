<?php

namespace app\modules\instructor\controllers;

use app\components\CanvasIntegration;
use app\models\AccessToken;
use app\models\Group;
use app\models\User;
use app\modules\instructor\resources\CanvasCourseResource;
use app\modules\instructor\resources\CanvasSectionResource;
use app\modules\instructor\resources\CanvasSetupResource;
use app\modules\instructor\resources\OAuth2Response;
use Yii;
use yii\base\Action;
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
    private $canvas;

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
                'courses' => ['GET'],
                'sections' => ['GET'],
            ]
        );
    }

    /**
     * Get the oauth2 token and refresh token from the canvas and save in the database.
     * @return array|void
     * @throws ServerErrorHttpException
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\httpclient\Exception
     */
    public function actionOauth2Response()
    {
        // Load and validate OAuth2 response
        $model = new OAuth2Response();
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
                return;
            } else {
                throw new ServerErrorHttpException(Yii::t('app', 'Canvas login failed'));
            }
        } else {
            throw new ServerErrorHttpException(Yii::t('app', 'Canvas login failed'));
        }
    }

    /**
     * Sets Canvas course and section fields before synchronization, then synchronizes course
     * @param int $groupID the id of the selected group
     * @return array|void
     * @throws BadRequestHttpException
     * @throws ConflictHttpException
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     * @throws ServerErrorHttpException
     */
    public function actionSetup($groupID)
    {
        $group = Group::findOne($groupID);

        if (is_null($group)) {
            throw new NotFoundHttpException(Yii::t('app','Group not found.'));
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
            $canvasCourse->canvasCourse
        );
        $this->canvas->synchronizeGroupData($group);
        $this->response->statusCode = 204;
    }

    /**
     * Synchronize the selected course and group with the canvas.
     * @param int $groupID the id of the selected group
     * @throws ConflictHttpException
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     * @throws ServerErrorHttpException
     */
    public function actionSync($groupID)
    {
        $group = Group::findOne($groupID);

        if (is_null($group)) {
            throw new NotFoundHttpException(Yii::t('app','Group not found.'));
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
     * Lists available Canvas courses
     * @return array|void
     * @throws ServerErrorHttpException
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

        return $out;
    }

    /**
     * Get the sections for the selected canvas course
     * @param int $courseID Canvas Course id
     * @return array|void
     */
    public function actionSections($courseID)
    {
        $user = User::findIdentity(Yii::$app->user->id);
        if (!$user->isAuthenticatedInCanvas) {
            $this->response->statusCode = 401;
            $this->response->headers->add('Proxy-Authenticate', CanvasIntegration::getLoginURL());
            return;
        }

        /*
         * Usually called after actionCourses, so refresh is not necessary
        if (!$this->canvas->refreshCanvasToken($user)) {
            throw new ServerErrorHttpException(Yii::t('app', 'Failed to refresh Canvas Token.'));
        }
        */

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
        return $out;
    }
}
