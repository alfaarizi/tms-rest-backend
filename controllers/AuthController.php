<?php

namespace app\controllers;

use app\models\LdapAuth;
use app\resources\LdapLoginResource;
use app\resources\LoginResponseResource;
use app\resources\SemesterResource;
use Yii;
use app\models\AccessToken;
use app\models\MockAuth;
use app\models\User;
use app\resources\MockLoginResource;
use yii\helpers\ArrayHelper;
use yii\web\BadRequestHttpException;
use yii\web\HttpException;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;

/**
 * This class controls the authentication actions.
 */
class AuthController extends BaseRestController
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['authenticator']['only'] = ['logout', 'logout-from-all', 'user-info', 'update-user-locale'];
        return $behaviors;
    }

    protected function verbs()
    {
        return ArrayHelper::merge(
            parent::verbs(),
            [
                'ldap-login' => ['POST'],
                'mock-login' => ['POST'],
                'logout' => ['POST'],
                'logout-from-all' => ['POST'],
            ]
        );
    }

    /**
     * Authenticate with LDAP
     * @return LoginResponseResource|array
     * @throws \yii\base\Exception
     * @throws \yii\base\NotSupportedException
     *
     * @OA\Post(
     *     path="/common/auth/ldap-login",
     *     tags={"Common Auth"},
     *     operationId="common::AuthController::actionLdapLogin",
     *     @OA\RequestBody(
     *         description="Ldap login data",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(ref="#/components/schemas/Common_LdapLoginResource_ScenarioDefault"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="successful login",
     *         @OA\JsonContent(ref="#/components/schemas/Common_LoginResponseResource_Read"),
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/422"),
     *     @OA\Response(response=500, ref="#/components/responses/500"),
     * )
     */
    public function actionLdapLogin()
    {
        // Show login form
        $model = new LdapLoginResource();
        $model->load(Yii::$app->request->post(), '');
        if (!$model->validate()) {
            $this->response->statusCode = 422;
            return $model->errors;
        }

        $authModel = new LdapAuth(
            Yii::$app->params['ldap']['host'],
            Yii::$app->params['ldap']['bindDN'],
            Yii::$app->params['ldap']['bindPasswd'],
            Yii::$app->params['ldap']['baseDN'],
            Yii::$app->params['ldap']['uidAttr']
        );
        // Try to authenticate.
        $authModel->username = $model->username;
        $authModel->password = $model->password;
        $authModel->login();

        if ($authModel->isAuthenticated) {
            $user = User::createOrUpdate($authModel);
            Yii::info("$user->name ($user->neptun) logged in", __METHOD__);
            $accessToken = AccessToken::createForUser($user);
            $accessToken->save();

            $loginResponse = new LoginResponseResource();
            $loginResponse->accessToken = $accessToken->token;
            $loginResponse->imageToken = $accessToken->imageToken;
            return $loginResponse;
        } else {
            Yii::info("Failed login: $model->username", __METHOD__);
            $model->addError('password', Yii::t('app', 'Invalid username or password'));
            $this->response->statusCode = 422;
            return $model->errors;
        }
    }

    /**
     * Mocked login for development mode
     * @return LoginResponseResource|array
     * @throws HttpException
     * @throws BadRequestHttpException
     * @throws \yii\base\Exception
     *
     * @OA\Post(
     *     path="/common/auth/mock-login",
     *     tags={"Common Auth"},
     *     operationId="common::AuthController::actionMockLogin",
     *     @OA\RequestBody(
     *         description="Mock login data",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(ref="#/components/schemas/Common_MockLoginResource_ScenarioDefault"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="successful login",
     *         @OA\JsonContent(ref="#/components/schemas/Common_LoginResponseResource_Read"),
     *     ),
     *     @OA\Response(response=400, ref="#/components/responses/400"),
     *     @OA\Response(response=422, ref="#/components/responses/422"),
     *     @OA\Response(response=500, ref="#/components/responses/500"),
     * )
     */
    public function actionMockLogin()
    {
        // No mocked login in production mode.
        if (YII_ENV_PROD) {
            throw new BadRequestHttpException(Yii::t('app', 'This action is not allowed in the current environment!'));
        }

        $model = new MockLoginResource();
        $model->load(Yii::$app->request->post(), '');

        if (!$model->validate()) {
            $this->response->statusCode = 422;
            return $model->errors;
        }

        $authModel = new MockAuth(
            $model->neptun,
            $model->name,
            $model->email,
            $model->isStudent,
            $model->isTeacher,
            $model->isAdmin
        );

        $user = User::findOne(['neptun' => $authModel->id]);
        if (!is_null($user)) {
            Yii::$app->authManager->revokeAll($user->id);
        }

        $user = User::createOrUpdate($authModel);
        $accessToken = AccessToken::createForUser($user);
        $accessToken->save();
        Yii::info("$user->name ($user->neptun) logged in", __METHOD__);

        $loginResponse = new LoginResponseResource();
        $loginResponse->accessToken = $accessToken->token;
        $loginResponse->imageToken = $accessToken->imageToken;
        return $loginResponse;
    }

    /**
     * Remove the currently used token
     * @OA\Post(
     *     path="/common/auth/logout",
     *     tags={"Common Auth"},
     *     operationId="common::AuthController::actionLogout",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=204,
     *         description="successful logout",
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/401"),
     *     @OA\Response(response=500, ref="#/components/responses/500"),
     * )
     */
    public function actionLogout()
    {
        $current = AccessToken::getCurrent();
        $current->delete();
        $this->response->statusCode = 204;
        Yii::info("A user has logged out", __METHOD__);
    }

    /**
     * Remove all tokens for the current user
     * @OA\Post(
     *     path="/common/auth/logout-from-all",
     *     tags={"Common Auth"},
     *     operationId="common::AuthController::actionLogoutFromAll",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=204,
     *         description="successful logout",
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/401"),
     *     @OA\Response(response=500, ref="#/components/responses/500"),
     * )
     */
    public function actionLogoutFromAll()
    {
        AccessToken::deleteAll(['userId' => Yii::$app->user->id]);
        $this->response->statusCode = 204;
        Yii::info("A user has logged out from all devices", __METHOD__);
    }
}
