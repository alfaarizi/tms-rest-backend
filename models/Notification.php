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
 * @property integer $groupID
 * @property string $message
 * @property string $startTime
 * @property string $endTime
 * @property string $scope
 * @property bool $dismissible
 */

class Notification extends \yii\db\ActiveRecord implements IOpenApiFieldTypes
{
    public const SCOPE_EVERYONE = 'everyone';
    public const SCOPE_USER = 'user';
    public const SCOPE_STUDENT = 'student';
    public const SCOPE_FACULTY = 'faculty';
    public const SCOPE_GROUP = 'group';

    // Supported scopes
    public const SCOPES = [
        self::SCOPE_EVERYONE,
        self::SCOPE_USER,
        self::SCOPE_STUDENT,
        self::SCOPE_FACULTY,
        self::SCOPE_GROUP,
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
            [['message', 'startTime', 'endTime', 'scope', 'dismissible'], 'required'],
            [['groupID'], 'integer'],
            [['message'], 'string'],
            [['scope'], 'in', 'range' => self::SCOPES],
            [['startTime', 'endTime'], 'safe'],
            [['dismissible'], 'boolean'],
            [
                'endTime',
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
            [
                'groupID',
                function ($attribute, $params, $validator) {
                    if (!is_null($this->groupID) && $this->scope != self::SCOPE_GROUP) {
                        $validator->addError(
                            $this,
                            $attribute,
                            Yii::t("app", "Scope must be group level when group ID is provided.")
                        );
                    }
                },
            ],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'groupID' => Yii::t('app', 'Group ID'),
            'message' => Yii::t('app', 'Message'),
            'startTime' => Yii::t('app', 'Start time'),
            'endTime' => Yii::t('app', 'End time'),
            'scope' => Yii::t('app', 'Scope'),
            'dismissible' => Yii::t('app', 'Dismissible'),
        ];
    }

    public function fieldTypes(): array
    {
        return [
            'id' => new OAProperty(['type' => 'integer']),
            'groupID' => new OAProperty(['type' => 'integer']),
            'message' => new OAProperty(['type' => 'string']),
            'startTime' => new OAProperty(['type' => 'string']),
            'endTime' => new OAProperty(['type' => 'string']),
            'scope' => new OAProperty(['type' => 'string', 'enum' => new OAList(self::SCOPES)]),
            'dismissible' => new OAProperty(['type' => 'boolean']),
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
        $this->dismissible = (bool)$this->dismissible;
    }

    public function getGroup(): \yii\db\ActiveQuery
    {
        return $this->hasOne(Group::class, ['id' => 'groupID']);
    }
}
