<?php

namespace app\models;

use Yii;
use yii\web\IdentityInterface;

/**
 * This is the model class for table "users".
 *
 * @property integer $id
 * @property string $name
 * @property string $neptun
 * @property string $email
 * @property string $locale
 * @property string $lastLoginTime
 * @property string $lastLoginIP
 * @property integer $canvasID
 * @property string $canvasToken
 * @property string $refreshToken
 * @property-read boolean $isAuthenticatedInCanvas
 *
 * @property Groups[] $groups
 * @property Subscription[] $subscriptions
 * @property StudentFile[] $files
 */
class User extends \yii\db\ActiveRecord implements \yii\web\IdentityInterface
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%users}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['neptun'], 'required'],
            [['name', 'email'], 'string', 'max' => 50],
            [['email'], 'email'],
            [['neptun'], 'string', 'max' => 6],
            [['neptun'], 'unique'],
            [['locale'], 'string', 'max' => 5],
            // lastLoginIP and lastLoginTime is unsafe for mass assignments
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'name' => Yii::t('app', 'Name'),
            'neptun' => Yii::t('app', 'Neptun'),
            'email' => Yii::t('app', 'Email'),
            'locale' => Yii::t('app', 'Locale'),
            'lastLoginTime' => Yii::t('app', 'Login time'),
            'lastLoginIP' => Yii::t('app', 'Login IP address'),
            'canvasID' => Yii::t('app', 'Canvas id'),
            'canvasToken' => Yii::t('app', 'Canvas token'),
            'refeshToken' => Yii::t('app', 'Refresh token'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSubscriptions()
    {
        return $this->hasMany(Subscription::class, ['userID' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getGroups()
    {
        return $this->hasMany(Group::class, ['id' => 'groupID'])
        ->viaTable('{{%instructor_groups}}', ['userID' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFiles()
    {
        return $this->hasMany(StudentFile::class, ['uploaderID' => 'id']);
    }

    /**
     *  Creates or updates a user's details.
     */
    public static function createOrUpdate(AuthInterface $authModel)
    {
        $user = static::findOne(['neptun' => $authModel->id]);

        // If the user null then create a new one.
        if ($user == null) {
            $user = new User();
            $user->neptun = $authModel->id;
            $user->locale = Yii::$app->user->locale ?: Yii::$app->language;
        }

        $user->name = $authModel->name;
        $user->email = $authModel->email;
        $user->lastLoginTime = date('Y/m/d H:i:s');
        $user->lastLoginIP = Yii::$app->request->userIP;
        $user->save();

        $authManager = Yii::$app->authManager;
        if ($authModel->isTeacher && !$authManager->checkAccess($user->id, 'faculty')) {
            $authManager->assign($authManager->getRole('faculty'), $user->id);
        }
        if (
            $authModel->isStudent && !$authManager->checkAccess($user->id, 'student') ||
            !$authModel->isStudent && !$authModel->isTeacher && !$authManager->checkAccess($user->id, 'student')
        ) {
            $authManager->assign($authManager->getRole('student'), $user->id);
        }
        if ($authModel->isAdmin && !$authManager->checkAccess($user->id, 'admin')) {
            $authManager->assign($authManager->getRole('admin'), $user->id);
        }

        return $user;
    }

    /**
     * Check that the user is authenticated in the canvas
     *
     * @return boolean whether the canvas token is not null.
     */
    public function getIsAuthenticatedInCanvas()
    {
        return $this->canvasToken !== null && $this->refreshToken !== null;
    }

    /**
     * Finds an identity by the given ID.
     * @param integer $id the ID to be looked for
     * @return IdentityInterface the identity object that matches the given ID.
     */
    public static function findIdentity($id)
    {
        return static::findOne($id);
    }

    /**
     * Finds an identity by the given token.
     *
     * @param mixed $token the token to be looked for
     * @param mixed $type the type of the token.
     * @return IdentityInterface the identity object that matches the given token.
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        $accessToken = AccessToken::findOne($token);

        if (is_null($accessToken)) {
            return null;
        }

        // Check token validation
        if ($accessToken->checkValidation()) {
            // If the token is valid, refresh validation date and return the associated user
            $accessToken->refreshValidUntil();

            return $accessToken->user;
        } else {
            return null;
        }
    }

    /**
     * Returns an ID that can uniquely identify a user identity.
     * @return integer an ID that uniquely identifies a user identity.
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Returns a key that can be used to check the validity of a given identity ID.
     *
     * Not implemented.
     * @return string a key that is used to check the validity of a given identity ID.
     * @throws \yii\base\NotSupportedException
     * @see validateAuthKey()
     */
    public function getAuthKey()
    {
        throw new \yii\base\NotSupportedException("Cookie-based authentication is not supported yet.");
    }

    /**
     * Validates the given auth key.
     *
     * Not implemented.
     * @param string $authKey the given auth key
     * @return boolean whether the given auth key is valid.
     * @see getAuthKey()
     */
    public function validateAuthKey($authKey)
    {
        return $this->authKey === $authKey;
    }
}
