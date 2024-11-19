<?php

namespace app\models;

use app\components\openapi\generators\OAList;
use app\components\openapi\generators\OAProperty;
use app\components\openapi\IOpenApiFieldTypes;
use Yii;
use yii\base\NotSupportedException;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\web\BadRequestHttpException;
use yii\web\IdentityInterface;

/**
 * This is the model class for table "users".
 *
 * @property integer $id
 * @property string $name
 * @property string $userCode
 * @property string $email
 * @property string $customEmail
 * @property string $locale
 * @property string $lastLoginTime
 * @property string $lastLoginIP
 * @property integer $canvasID
 * @property bool $isStudent
 * @property bool $isFaculty
 * @property bool $isAdmin
 * @property string|null $canvasToken
 * @property string|null $refreshToken
 * @property string $canvasTokenExpiry
 * @property boolean $customEmailConfirmed
 * @property string $customEmailConfirmationCode
 * @property string $customEmailConfirmationExpiry
 * @property string $notificationTarget
 * @property-read boolean $isAuthenticatedInCanvas
 * @property-read string $notificationEmail
 * @property-read string $authKey
 *
 * @property Group[] $groups
 * @property Subscription[] $subscriptions
 * @property Submission[] $files
 */
class User extends ActiveRecord implements IdentityInterface, IOpenApiFieldTypes
{
    public const SCENARIO_SETTINGS = 'settings';
    private const NOTIFICATION_TARGET = [
        'official' => 'Official email address',
        'custom' => 'Custom email address',
        'none' => 'Don’t send notifications'
    ];

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%users}}';
    }

    /**
     * Get a 'map' of the supported notification targets and their language-specific translations.
     *
     * @param string|null $language the language code (e.g. `en-US`, `en`). If this is null, the current
     * [[\yii\base\Application::language|application language]] will be used.
     * @return array An associative array of notification target identifiers and their
     * language-specific translations.
     */
    public static function notificationTargetMap(?string $language = null): array
    {
        return array_map(function ($en) use ($language) {
            return Yii::t('app', $en, [], $language);
        }, self::NOTIFICATION_TARGET);
    }

    /**
     *  Creates or updates a user's details.
     */
    public static function createOrUpdate(AuthInterface $authModel)
    {
        $user = static::findOne(['userCode' => $authModel->id]);

        // If the user null then create a new one.
        if ($user == null) {
            $user = new User();
            $user->userCode = $authModel->id;
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
     * Get the user corresponding to an email confirmation code.
     *
     * @param string $code The confirmation code.
     * @return static|null The user corresponding to the code, if any.
     */
    public static function findByConfirmationCode(string $code): ?User
    {
        return static::find()->where(
            [
                'and',
                ['=', 'customEmailConfirmationCode', $code],
                ['>', 'customEmailConfirmationExpiry', date('Y/m/d H:i:s')]
            ]
        )->one();
    }

    /**
     * Finds an identity by the given ID.
     * @param integer $id the ID to be looked for
     * @return null|User the identity object that matches the given ID.
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
     * @return IdentityInterface|null the identity object that matches the given token.
     */
    public static function findIdentityByAccessToken($token, $type = null): ?IdentityInterface
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
     * Searches the users that match the given text
     *
     * @param string $text the text to be looked for
     * @return User[] all users that match the given text
     * @throws BadRequestHttpException
     */
    public static function search(string $text): array
    {
        // text has to be at least 3 characters long to avoid too many results
        if (strlen($text) < 3) {
            throw new BadRequestHttpException(Yii::t('app', 'Search text is too short'));
        }

        $lowerCaseText = mb_strtolower($text);

        return static::find()->where(
            [
                'or',
                ['like', 'LOWER(userCode)', $lowerCaseText],
                ['like', 'LOWER(name)', $lowerCaseText]
            ]
        )->all();
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['userCode'], 'required'],
            [['locale', 'notificationTarget'], 'required', 'on' => self::SCENARIO_SETTINGS],
            [['name', 'email', 'customEmail'], 'string', 'max' => 50],
            [['email', 'customEmail'], 'email'],
            [['userCode'], 'match', 'pattern' => Yii::$app->params['userCodeFormat']],
            [['userCode'], 'unique'],
            [['locale'], 'in', 'range' => array_keys(Yii::$app->params['supportedLocale'])],
            [['customEmailConfirmed'], 'boolean'],
            [
                ['notificationTarget'],
                'in',
                'range' => function ($model) {
                    $range = self::NOTIFICATION_TARGET;
                    if (!$model->customEmailConfirmed) {
                        unset($range['custom']);
                    }
                    return array_keys($range);
                }
            ],
            // lastLoginIP, lastLoginTime and custom email-related fields are unsafe for mass assignments
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
            'userCode' => Yii::t('app', 'userCode'),
            'email' => Yii::t('app', 'Official email address'),
            'customEmail' => Yii::t('app', 'Custom email address'),
            'locale' => Yii::t('app', 'Locale'),
            'lastLoginTime' => Yii::t('app', 'Login time'),
            'lastLoginIP' => Yii::t('app', 'Login IP address'),
            'canvasID' => Yii::t('app', 'Canvas id'),
            'canvasToken' => Yii::t('app', 'Canvas token'),
            'refeshToken' => Yii::t('app', 'Refresh token'),
            'notificationTarget' => Yii::t('app', 'Notification target'),
        ];
    }

    public function fieldTypes(): array
    {
        return [
            'id' => new OAProperty(['type' => 'integer']),
            'name' => new OAProperty(['type' => 'string']),
            'userCode' => new OAProperty(['type' => 'string']),
            'email' => new OAProperty(['type' => 'string']),
            'customEmail' => new OAProperty(['type' => 'string']),
            'isStudent' => new OAProperty(['type' => 'boolean']),
            'isFaculty' => new OAProperty(['type' => 'boolean']),
            'isAdmin' => new OAProperty(['type' => 'boolean']),
            'locale' => new OAProperty(
                [
                    'type' => 'string',
                    'enum' => new OAList(array_keys(Yii::$app->params['supportedLocale']))
                ]
            ),
            'lastLoginTime' => new OAProperty(['type' => 'string']),
            'lastLoginIP' => new OAProperty(['type' => 'string']),
            'customEmailConfirmed' => new OAProperty(['type' => 'string']),
            'customEmailConfirmationCode' => new OAProperty(['type' => 'string']),
            'customEmailConfirmationExpiry' => new OAProperty(['type' => 'string']),
            'isAuthenticatedInCanvas' => new OAProperty(['type' => 'string']),
            'notificationEmail' => new OAProperty(['type' => 'string']),
            'notificationTarget' => new OAProperty(
                ['type' => 'string', 'enum' => new OAList(array_keys(self::NOTIFICATION_TARGET))]
            ),
        ];
    }

    /**
     * @inheritdoc
     */
    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_SETTINGS] = ['locale', 'customEmail', 'notificationTarget'];
        return $scenarios;
    }

    /**
     * @return ActiveQuery
     */
    public function getSubscriptions()
    {
        return $this->hasMany(Subscription::class, ['userID' => 'id']);
    }

    /**
     * @return ActiveQuery
     */
    public function getGroups()
    {
        return $this->hasMany(Group::class, ['id' => 'groupID'])
            ->viaTable('{{%instructor_groups}}', ['userID' => 'id']);
    }

    /**
     * @return bool
     */
    public function getIsStudent(): bool
    {
        $roles = Yii::$app->authManager->getRolesByUser($this->id);
        return array_key_exists('student', $roles);
    }

    /**
     * @return bool
     */
    public function getIsAdmin(): bool
    {
        $roles = Yii::$app->authManager->getRolesByUser($this->id);
        return array_key_exists('admin', $roles);
    }

    /**
     * @return bool
     */
    public function getIsFaculty(): bool
    {
        $roles = Yii::$app->authManager->getRolesByUser($this->id);
        return array_key_exists('faculty', $roles);
    }

    /**
     * @return ActiveQuery
     */
    public function getFiles()
    {
        return $this->hasMany(Submission::class, ['uploaderID' => 'id']);
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
     * @throws NotSupportedException
     * @see validateAuthKey()
     */
    public function getAuthKey()
    {
        throw new NotSupportedException("Cookie-based authentication is not supported yet.");
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

    /**
     * Get the email address used for notifications.
     *
     * @return string|null The notification email, or `null` if it’s unknown
     * or notifications are disabled.
     */
    public function getNotificationEmail(): ?string
    {
        switch ($this->notificationTarget) {
            case 'none':
                return null;
            case 'custom':
                if ($this->customEmailConfirmed) {
                    return $this->customEmail;
                }
            // else fall through
            case 'official':
            default:
                return $this->email;
        }
    }

    /**
     * Get (and store) custom email confirmation code if the custom
     * email is dirty. Saving to the database is the caller’s
     * responsibility.
     * @return string|null The confirmation code, or null if the custom
     * email is not dirty.
     */
    public function getConfirmationCodeIfNecessary(): ?string
    {
        if ($this->getDirtyAttributes(['customEmail']) && $this->customEmail) {
            $code = Yii::$app->getSecurity()->generateRandomString();
            $this->customEmailConfirmationCode = $code;
            $this->customEmailConfirmationExpiry = date('Y/m/d H:i:s', strtotime('+24hours'));
            return $code;
        } else {
            return null;
        }
    }

    /**
     * @inheritdoc
     */
    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }

        if (!$insert && $this->getDirtyAttributes(['customEmail'])) {
            $this->customEmailConfirmed = false;
        }
        return true;
    }
}
