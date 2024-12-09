<?php

namespace app\models;

use app\behaviors\ISODateTimeBehavior;
use app\components\openapi\generators\OAList;
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
 * @property string $scope
 * @property bool $dismissable
 */

class Notification extends \yii\db\ActiveRecord implements IOpenApiFieldTypes
{
    public const SCOPE_EVERYONE = 'everyone';
    public const SCOPE_USER = 'user';
    public const SCOPE_STUDENT = 'student';
    public const SCOPE_FACULTY = 'faculty';

    // Supported scopes
    public const SCOPES = [
        self::SCOPE_EVERYONE,
        self::SCOPE_USER,
        self::SCOPE_STUDENT,
        self::SCOPE_FACULTY,
    ];

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
            [['message', 'startTime', 'endTime', 'scope', 'dismissable'], 'required'],
            [['message'], 'string'],
            [['scope'], 'in', 'range' => self::SCOPES],
            [['startTime', 'endTime'], 'safe'],
            [['dismissable'], 'boolean'],
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
            'scope' => Yii::t('app', 'Scope'),
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
            'scope' => new OAProperty(['type' => 'string', 'enum' => new OAList(self::SCOPES)]),
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
        $this->dismissable = (bool)$this->dismissable;
    }
}
