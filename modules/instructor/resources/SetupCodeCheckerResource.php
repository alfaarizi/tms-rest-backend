<?php

namespace app\modules\instructor\resources;

use app\components\openapi\generators\OAProperty;
use app\components\openapi\IOpenApiFieldTypes;
use app\models\Model;

class SetupCodeCheckerResource extends Model implements IOpenApiFieldTypes
{
    /**
     * @var bool
     */
    public $staticCodeAnalysis;

    /**
     * @var string
     */
    public $staticCodeAnalyzerTool;

    /**
     * @var string
     */
    public $staticCodeAnalyzerInstructions;

    /**
     * @var string
     */
    public $codeCheckerCompileInstructions;

    /**
     * @var string
     */
    public $codeCheckerToggles;

    /**
     * @var string
     */
    public $codeCheckerSkipFile;

    /**
     * @var string
     */
    public $reevaluateStaticCodeAnalysis;


    public function rules(): array
    {
        return [
            [['staticCodeAnalysis', 'reevaluateStaticCodeAnalysis'], 'boolean'],
            [['staticCodeAnalysis', 'reevaluateStaticCodeAnalysis'], 'required'],
            [
                [
                    'staticCodeAnalyzerTool',
                    'staticCodeAnalyzerInstructions',
                    'codeCheckerCompileInstructions',
                    'codeCheckerToggles',
                    'codeCheckerSkipFile'
                ],
                'string'
            ]
        ];
    }

    public function fieldTypes(): array
    {
        return  [
            'staticCodeAnalysis' => new OAProperty(['type' => 'boolean']),
            'staticCodeAnalyzerTool' => new OAProperty(['type' => 'string']),
            'staticCodeAnalyzerInstructions' => new OAProperty(['type' => 'string']),
            'codeCheckerSkipFile' => new OAProperty(['type' => 'string']),
            'codeCheckerCompileInstructions' => new OAProperty(['type' => 'string']),
            'codeCheckerToggles' => new OAProperty(['type' => 'string']),
            'reevaluateStaticCodeAnalysis' => new OAProperty(['type' => 'boolean']),
        ];
    }
}
