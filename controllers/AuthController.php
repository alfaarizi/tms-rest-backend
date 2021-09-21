<?php

namespace app\controllers;

use app\models\LdapAuth;
use app\resources\LdapLoginResource;
use app\resources\SemesterResource;
use app\resources\UserInfoResource;
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
                'user-info' => ['GET'],
                'update-user-locale' => ['PUT']
            ]
        );
    }

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
            return [
                'accessToken' => $accessToken->token,
                'imageToken' => $accessToken->imageToken,
                'userInfo' => $this->getUserInfo($user->id)
            ];
        } else {
            Yii::info("Failed login: $model->username", __METHOD__);
            $model->addError('password', Yii::t('app', 'Invalid username or password'));
            $this->response->statusCode = 422;
            return $model->errors;
        }
    }

    /**
     * Development env only
     * @return array
     * @throws HttpException
     * @throws BadRequestHttpException
     */
    public function actionMockLogin()
    {
        // No mocked login in production mode.
        if (YII_ENV_PROD) {
            throw new BadRequestHttpException(Yii::t('app', 'Mock login is not allowed in production mode!'));
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

        return [
            'accessToken' => $accessToken->token,
            'imageToken' => $accessToken->imageToken,
            'userInfo' => $this->getUserInfo($user->id)
        ];
    }

    /**
     * @return UserInfoResource
     */
    public function actionUserInfo()
    {
        $userID = Yii::$app->user->id;
        return $this->getUserInfo($userID);
    }

    /**
     * Updates the locale of the current user
     * @throws BadRequestHttpException
     */
    public function actionUpdateUserLocale()
    {
        $locale = Yii::$app->request->post('locale');
        if (!key_exists($locale, Yii::$app->params['supportedLocale'])) {
            throw new NotFoundHttpException(Yii::t('app', 'The selected locale is not supported'));
        }

        $user = User::findOne(Yii::$app->user->id);
        $user->locale = $locale;
        if (!$user->save()) {
            throw new ServerErrorHttpException(Yii::t("app", "A database error occurred"));
        }
        $this->response->statusCode = 204;
    }

    /**
     * @param int $userID
     * @return UserInfoResource
     */
    private function getUserInfo($userID)
    {
        $user = User::findOne($userID);
        $roles = Yii::$app->authManager->getRolesByUser($userID);

        $userInfo = new UserInfoResource();
        $userInfo->id = $userID;
        $userInfo->neptun = $user->neptun;
        $userInfo->locale = $user->locale;
        $userInfo->isStudent = array_key_exists('student', $roles);
        $userInfo->isFaculty = array_key_exists('faculty', $roles);
        $userInfo->isAdmin = array_key_exists('admin', $roles);
        $userInfo->actualSemester = SemesterResource::findOne(['actual' => 1]);

        $userInfo->isAutoTestEnabled = Yii::$app->params['evaluator']['enabled'];
        $userInfo->isVersionControlEnabled = Yii::$app->params['versionControl']['enabled'];
        $userInfo->isCanvasEnabled = Yii::$app->params['canvas']['enabled'];

        return $userInfo;
    }

    /**
     * Removes the currently used token
     */
    public function actionLogout()
    {
        $current = AccessToken::getCurrent();
        $current->delete();
        $this->response->statusCode = 204;
    }

    /**
     * Removes all tokens for the current user
     */
    public function actionLogoutFromAll()
    {
        AccessToken::deleteAll(['userId' => Yii::$app->user->id]);
        $this->response->statusCode = 204;
    }
}
