<?php

namespace app\models;

use app\components\openapi\generators\OAProperty;
use app\components\openapi\IOpenApiFieldTypes;
use Yii;

/**
 * This is the model class for table "testcases".
 *
 * @property integer $id
 * @property integer $taskID
 * @property string $arguments
 * @property string $input
 * @property string $output
 *
 * @property Task $task
 */
class TestCase extends \yii\db\ActiveRecord implements IOpenApiFieldTypes
{
    public const SCENARIO_CREATE = 'create';
    public const SCENARIO_UPDATE = 'update';

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_CREATE] = ['taskID', 'arguments', 'input', 'output'];
        $scenarios[self::SCENARIO_UPDATE] = ['arguments', 'input', 'output'];
        return $scenarios;
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%test_cases}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['taskID', 'output'], 'required'],
            [['taskID'], 'integer'],
            [['arguments', 'input', 'output'], 'string'],
            [
                ['taskID'],
                'exist',
                'skipOnError' => true,
                'targetClass' => Task::class,
                'targetAttribute' => ['taskID' => 'id']
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'taskID' => Yii::t('app', 'Task ID'),
            'arguments' => Yii::t('app', 'Arguments'),
            'input' => Yii::t('app', 'Input'),
            'output' => Yii::t('app', 'Output'),
        ];
    }

    public function fieldTypes(): array
    {
        return [
            'id' => new OAProperty(['ref' => '#/components/schemas/int_id']),
            'taskID' => new OAProperty(['ref' => '#/components/schemas/int_id']),
            'arguments' => new OAProperty(['type' => 'string']),
            'input' => new OAProperty(['type' => 'string']),
            'output' => new OAProperty(['type' => 'string']),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTask()
    {
        return $this->hasOne(Task::class, ['id' => 'taskID']);
    }
}
