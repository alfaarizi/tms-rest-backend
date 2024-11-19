<?php

namespace app\modules\instructor\resources;

use app\components\openapi\generators\OAProperty;
use app\resources\CourseResource;
use yii\helpers\ArrayHelper;

class EvaluatorTemplateResource extends \app\models\EvaluatorTemplate
{
    public function fields()
    {
        return [
            'id',
            'name',
            'enabled',
            'course',
            'os',
            'image',
            'autoTest',
            'appType',
            'port',
            'compileInstructions',
            'runInstructions',
            'staticCodeAnalysis',
            'staticCodeAnalyzerTool',
            'codeCheckerCompileInstructions',
            'staticCodeAnalyzerInstructions',
            'codeCheckerSkipFile',
            'codeCheckerToggles',
        ];
    }

    public function extraFields()
    {
        return [];
    }

    public function fieldTypes(): array
    {
        return ArrayHelper::merge(
            parent::fieldTypes(),
            [
                'course' => new OAProperty(['ref' => '#/components/schemas/Common_CourseResource_Read']),
            ]
        );
    }

    /**
     * @inheritdoc
     */
    public function getCourse()
    {
        return $this->hasOne(CourseResource::class, ['id' => 'courseID']);
    }
}
