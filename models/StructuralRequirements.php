<?php

namespace app\models;

use app\components\openapi\generators\OAList;
use app\components\openapi\generators\OAProperty;
use app\components\openapi\IOpenApiFieldTypes;
use Yii;

/**
* This is the model class for table "structural_requirements".
*
* @property integer $id
* @property integer $taskID
* @property string $regexExpression
* @property string $type
*
* @property-read Task $task
*/
class StructuralRequirements extends \yii\db\ActiveRecord implements IOpenApiFieldTypes
{
    public const SCENARIO_UPDATE = 'update';
    public const SCENARIO_CREATE = 'create';
    public const SUBMISSION_INCLUDES = 'Includes';
    public const SUBMISSION_EXCLUDES = 'Excludes';
    public const STRUCTURAL_REQUIREMENT_TYPE = [
        self::SUBMISSION_INCLUDES,
        self::SUBMISSION_EXCLUDES,
    ];

    /**
    * @inheritdoc
    */
    public static function tableName(): string
    {
        return '{{%structural_requirements}}';
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_CREATE] = ['taskID', 'regexExpression', 'type'];
        $scenarios[self::SCENARIO_UPDATE] = ['regexExpression', 'type'];

        return $scenarios;
    }

    public function rules(): array
    {
        return [
            [['taskID', 'regexExpression', 'type'], 'required'],
            [['taskID'], 'integer'],
            [['regexExpression'], 'string'],
            [['regexExpression'], 'validateRegex'],
        ];
    }

    public function validateRegex()
    {
        try {
            preg_match('/' . $this->regexExpression . '/', "Test regex");
        } catch (\Exception $e) {
            $this->addError(
                'regexExpression',
                Yii::t('app', 'Invalid regular expression: ') . $this->regexExpression
            );
        }
    }

    public function attributeLabels(): array
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'taskID' => Yii::t('app', 'Task ID'),
            'regexExpression' => Yii::t('app', 'Regular Expression'),
            'type' => Yii::t('app', 'Structural Requirement Type'),
        ];
    }

    public function fieldTypes(): array
    {
        return [
            'id' => new OAProperty(['ref' => '#/components/schemas/int_id']),
            'taskID' => new OAProperty(['ref' => '#/components/schemas/int_id']),
            'type' => new OAProperty(['type' => 'string', 'enum' => new OAList(self::STRUCTURAL_REQUIREMENT_TYPE)]),
            'regexExpression' => new OAProperty(['type' => 'string']),
        ];
    }

    public function getTask(): \yii\db\ActiveQuery
    {
        return $this->hasOne(Task::class, ['id' => 'taskID']);
    }
}
