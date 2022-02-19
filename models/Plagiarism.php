<?php

namespace app\models;

use app\components\openapi\generators\OAProperty;
use app\components\openapi\IOpenApiFieldTypes;
use Yii;

/**
 * This is the model class for table "plagiarisms".
 *
 * @property integer $id
 * @property integer $requesterID
 * @property string $taskIDs
 * @property string $userIDs
 * @property integer $semesterID
 * @property string $name
 * @property string $description
 * @property string $response
 * @property integer $ignoreThreshold
 *
 * @property Semester $semester
 * @property User $requester
 *
 * ToDo:
 *      -Rename to Plagiarism Validations.
 */
class Plagiarism extends \yii\db\ActiveRecord implements IOpenApiFieldTypes
{
    public const SCENARIO_UPDATE = 'update';

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%plagiarisms}}';
    }

    /**
     * @inheritdoc
     */
    public function scenarios()
    {
        $scenarios = parent::scenarios();
        // Only the name and description can be mass assigned when updating.
        $scenarios[self::SCENARIO_UPDATE] = ['name', 'description'];
        return $scenarios;
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['requesterID', 'taskIDs', 'userIDs', 'semesterID', 'name', 'ignoreThreshold'], 'required'],
            [['requesterID', 'semesterID', 'ignoreThreshold'], 'integer'],
            [['taskIDs', 'userIDs'], 'string', 'max' => 65535],
            [['response'], 'string', 'max' => 300],
            [['name'], 'string', 'max' => 30],
            [['ignoreThreshold'], 'integer', 'min' => 1, 'max' => 1000],
            ['ignoreThreshold', 'default', 'value' => 10],
            [['description'], 'string'],
            [
                ['semesterID'],
                'exist',
                'skipOnError' => true,
                'targetClass' => Semester::class,
                'targetAttribute' => ['semesterID' => 'id']
            ],
            [
                ['requesterID'],
                'exist',
                'skipOnError' => true,
                'targetClass' => User::class,
                'targetAttribute' => ['requesterID' => 'id']
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'requesterID' => 'Requester ID',
            'taskIDs' => 'Task Ids',
            'userIDs' => 'User Ids',
            'semesterID' => 'Semester ID',
            'name' => Yii::t('app', 'Name'),
            'description' => Yii::t('app', 'Description'),
            'response' => Yii::t('app', 'Response'),
            'ignoreThreshold' => Yii::t('app', 'Ignore threshold'),
        ];
    }

    public function fieldTypes(): array
    {
        return [
            'id' => new OAProperty(['ref' => '#/components/schemas/int_id']),
            'requesterID' => new OAProperty(['ref' => '#/components/schemas/int_id']),
            'taskIDs' => new OAProperty(['ref' => '#/components/schemas/int_id']),
            'userIDs' => new OAProperty(['ref' => '#/components/schemas/int_id']),
            'semesterID' => new OAProperty(['ref' => '#/components/schemas/int_id']),
            'name' => new OAProperty(['type' => 'string']),
            'description' => new OAProperty(['type' => 'string']),
            'response' => new OAProperty(['type' => 'string']),
            'ignoreThreshold' => new OAProperty(['type' => 'integer']),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSemester()
    {
        return $this->hasOne(Semester::class, ['id' => 'semesterID']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRequester()
    {
        return $this->hasOne(User::class, ['id' => 'requesterID']);
    }
}
