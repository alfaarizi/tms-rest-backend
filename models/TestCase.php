<?php

namespace app\models;

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
class TestCase extends \yii\db\ActiveRecord
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

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTask()
    {
        return $this->hasOne(Task::class, ['id' => 'taskID']);
    }
}
