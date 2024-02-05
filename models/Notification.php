<?php

namespace app\models;

use app\behaviors\ISODateTimeBehavior;
use app\components\openapi\IOpenApiFieldTypes;
use app\components\openapi\generators\OAProperty;
use app\models\queries\NotificationQuery;
use Yii;

/**
 * This is the model class for table "notifications".
 *
 * @property integer $id
 * @property string $message
 * @property string $startTime
 * @property string $endTime
 * @property bool $isAvailableForAll
 * @property bool $dismissable
 */

class Notification extends \yii\db\ActiveRecord implements IOpenApiFieldTypes
{
    /**
     * @inheritdoc
     */
    public static function tableName(): string
    {
        return '{{%notifications}}';
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors(): array
    {
        return [
            [
                'class' => ISODateTimeBehavior::class,
                'attributes' => ['startTime', 'endTime'],
            ]
        ];
    }

    public function rules(): array
    {
        return [
            [['message', 'startTime', 'endTime', 'isAvailableForAll', 'dismissable'], 'required'],
            [['message'], 'string'],
            [['startTime', 'endTime'], 'safe'],
            [['isAvailableForAll', 'dismissable'], 'boolean'],
            [
                ['endTime'],
                function ($attribute, $params, $validator) {
                    if (strtotime($this->endTime) < strtotime($this->startTime)) {
                        $validator->addError(
                            $this,
                            $attribute,
                            Yii::t("app", "End time must be after start time")
                        );
                    }
                }
            ],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'message' => Yii::t('app', 'Message'),
            'startTime' => Yii::t('app', 'Start time'),
            'endTime' => Yii::t('app', 'End time'),
            'isAvailableForAll' => Yii::t('app', 'Is available for all users'),
            'dismissable' => Yii::t('app', 'Dismissable'),
        ];
    }

    public function fieldTypes(): array
    {
        return [
            'id' => new OAProperty(['type' => 'integer']),
            'message' => new OAProperty(['type' => 'string']),
            'startTime' => new OAProperty(['type' => 'string']),
            'endTime' => new OAProperty(['type' => 'string']),
            'isAvailableForAll' => new OAProperty(['type' => 'boolean']),
            'dismissable' => new OAProperty(['type' => 'boolean']),
        ];
    }

    /**
     * {@inheritdoc}
     * @return NotificationQuery the active query used by this AR class.
     */
    public static function find(): NotificationQuery
    {
        return new NotificationQuery(get_called_class());
    }

    /**
     * {@inheritdoc}
     */
    public function afterFind(): void
    {
        parent::afterFind();
        $this->isAvailableForAll = (bool)$this->isAvailableForAll;
        $this->dismissable = (bool)$this->dismissable;
    }
}
