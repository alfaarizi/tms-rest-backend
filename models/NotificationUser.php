<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "notification_users".
 *
 * @property int|null $userID
 * @property int|null $notificationID
 *
 * @property-read User $user
 * @property-read Notification $notification
 */
class NotificationUser extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName(): string
    {
        return '{{%notification_users}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
            [['userID', 'notificationID'], 'integer'],
            [['userID', 'notificationID'], 'required'],
            [
                ['userID'],
                'unique',
                'targetAttribute' => ['notificationID', 'userID'],
                'message' => Yii::t('app', 'The combination of  Notification ID and User ID has already been taken.')
            ]
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels(): array
    {
        return [
            'userID' => Yii::t('app', 'User ID'),
            'notificationID' => Yii::t('app', 'Notification ID'),
        ];
    }

    public function getUser(): \yii\db\ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'userID']);
    }

    public function getNotification(): \yii\db\ActiveQuery
    {
        return $this->hasOne(Notification::class, ['id' => 'notificationID']);
    }
}
