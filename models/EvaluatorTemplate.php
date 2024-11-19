<?php

namespace app\models;

use app\components\openapi\generators\OAProperty;
use app\components\openapi\IOpenApiFieldTypes;
use Yii;

/**
 * This is the model class for table "evaluator_templates".
 *
 * @property integer $id
 * @property string $name
 * @property boolean $enabled
 * @property integer $courseID
 * @property string $os
 * @property string|null $image
 * @property boolean $autoTest
 * @property string|null $appType
 * @property integer|null $port
 * @property string $compileInstructions
 * @property string $runInstructions
 * @property boolean $staticCodeAnalysis
 * @property string|null $staticCodeAnalyzerTool
 * @property string|null $staticCodeAnalyzerInstructions
 * @property string|null $codeCheckerCompileInstructions
 * @property string|null $codeCheckerToggles
 * @property string|null $codeCheckerSkipFile
 *
 * @property Course $course
 */
class EvaluatorTemplate extends \yii\db\ActiveRecord implements IOpenApiFieldTypes
{
    /**
     * @inheritdoc
     */
    public static function tableName(): string
    {
        return '{{%evaluator_templates}}';
    }

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return [
            [['name', 'enabled', 'os', 'image', 'autoTest', 'appType', 'staticCodeAnalysis'], 'required'],
            [['name'], 'string', 'max' => 40],
            [['image', 'staticCodeAnalyzerTool'], 'string', 'max' => 255],
            [['codeCheckerCompileInstructions', 'staticCodeAnalyzerInstructions', 'codeCheckerToggles', 'codeCheckerSkipFile'], 'string', 'max' => 1000],
            [['compileInstructions', 'runInstructions'], 'string'],
            [['enabled', 'autoTest', 'staticCodeAnalysis'], 'boolean'],
            [['os'], 'in', 'range' => Task::TEST_OS],
            [['appType'], 'in', 'range' => Task::APP_TYPES],
            [['courseID'], 'integer']
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
            'enabled' => Yii::t('app', 'Template enabled'),
            'courseID' => Yii::t('app', 'Course ID'),
            'os' => Yii::t('app', 'Operating System'),
            'image' => Yii::t('app', 'Docker Image'),
            'autoTest' => Yii::t('app', 'Automatic Testing'),
            'appType' => Yii::t('app', 'Application type'),
            'port' => Yii::t('app', 'Port'),
            'compileInstructions' => Yii::t('app', 'Compile Instructions'),
            'runInstructions' => Yii::t('app', 'Run Instructions'),
            'staticCodeAnalysis' => Yii::t('app', 'Static Code Analysis'),
            'staticCodeAnalyzerTool' => Yii::t('app', 'Static Code Analyzer Tool'),
            'staticCodeAnalyzerInstructions' => Yii::t('app', 'Static Code Analyzer Instructions'),
            'codeCheckerCompileInstructions' => Yii::t('app', 'CodeChecker Compiler Instructions'),
            'codeCheckerToggles' => Yii::t('app', 'CodeChecker Toggles'),
            'codeCheckerSkipFile' => Yii::t('app', 'CodeChecker Skipfile'),
        ];
    }

    public function fieldTypes(): array
    {
        return [
            'id' => new OAProperty(['type' => 'integer']),
            'name' => new OAProperty(['type' => 'string']),
            'enabled' => new OAProperty(['type' => 'boolean']),
            'courseID' => new OAProperty(['type' => 'integer']),
            'os' => new OAProperty(['type' => 'string']),
            'image' => new OAProperty(['type' => 'string']),
            'autoTest' => new OAProperty(['type' => 'boolean']),
            'appType' => new OAProperty(['type' => 'string']),
            'port' => new OAProperty(['type' => 'integer']),
            'compileInstructions' => new OAProperty(['type' => 'string']),
            'runInstructions' => new OAProperty(['type' => 'string']),
            'staticCodeAnalysis' => new OAProperty(['type' => 'boolean']),
            'staticCodeAnalyzerTool' => new OAProperty(['type' => 'string']),
            'codeCheckerCompileInstructions' => new OAProperty(['type' => 'string']),
            'staticCodeAnalyzerInstructions' => new OAProperty(['type' => 'string']),
            'codeCheckerSkipFile' => new OAProperty(['type' => 'string']),
            'codeCheckerToggles' => new OAProperty(['type' => 'string']),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCourse()
    {
        return $this->hasOne(Course::class, ['id' => 'courseID']);
    }
}
