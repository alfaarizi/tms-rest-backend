<?php

namespace app\models;

use app\exceptions\TokenExpiredException;
use Yii;
use DateTime;
use DateInterval;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "access_tokens".
 *
 * @property string $token
 * @property int $userId
 * @property string $validUntil
 * @property string $imageToken
 * @property string $canvasOAuth2State
 *
 * @property User $user
 */
class AccessToken extends ActiveRecord
{
    public const ACCESS_TOKEN_LENGTH = 128;
    public const IMAGE_TOKEN_LENGTH = 32;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%access_tokens}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['token', 'userId', 'validUntil', 'imageToken'], 'required'],
            [['userId'], 'integer'],
            [['validUntil'], 'safe'],
            [['token', 'imageToken', 'canvasOAuth2State'], 'string', 'max' => 255],
            [['token'], 'unique'],
            [['userId'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['userId' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'token' => 'Token',
            'userId' => 'User ID',
            'validUntil' => 'Valid Until',
            'imageToken' => 'Image Token',
            'canvasOAuth2State' => 'Canvas OAuth2 State'
        ];
    }

    /**
     * Gets query for [[User]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'userId']);
    }

    /**
     * @return bool
     * @throws \Exception
     * Checks validation of the current token
     */
    public function checkValidation()
    {
        $now = new DateTime();
        $valid = new DateTime($this->validUntil);

        return $now < $valid;
    }

    /**
     * Refreshes  validUntil value
     */
    public function refreshValidUntil()
    {
        if (!$this->checkValidation()) {
            throw new TokenExpiredException("You cannot refresh an expired token");
        }

        $this->validUntil = self::calculateNewValidation();
        $this->save();
    }

    /**
     * @param User $user
     * @return AccessToken
     * @throws \yii\base\Exception
     * Creates access token for the given user. Token is not saved to the database automatically!
     */
    public static function createForUser($user)
    {
        $authToken = new AccessToken();
        $authToken->userId = $user->id;

        $randomString = Yii::$app->security->generateRandomString(self::ACCESS_TOKEN_LENGTH);
        $authToken->token = "{$user->neptun}-{$randomString}";

        $randomString = Yii::$app->security->generateRandomString(self::IMAGE_TOKEN_LENGTH);
        $authToken->imageToken = "{$user->neptun}-{$randomString}";

        $authToken->validUntil = self::calculateNewValidation();

        return $authToken;
    }

    /**
     * @return string
     * Calculates new validation datetime value
     */
    private static function calculateNewValidation()
    {
        $date = new DateTime();
        $date->add(DateInterval::createFromDateString(Yii::$app->params['accessTokenExtendValidationBy']));

        return $date->format('Y-m-d H:i:s');
    }

    /**
     * Returns access token for the currently logged-in user.
     * @return AccessToken|null
     */
    public static function getCurrent(): ?AccessToken
    {
        $authHeader = Yii::$app->request->headers->get('Authorization');
        $token = explode(' ', $authHeader)[1];
        return AccessToken::findOne($token);
    }
}
