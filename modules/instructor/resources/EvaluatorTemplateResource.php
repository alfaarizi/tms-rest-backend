<?php

namespace app\modules\instructor\resources;

use app\components\openapi\generators\OAList;
use app\components\openapi\generators\OAProperty;
use app\components\openapi\IOpenApiFieldTypes;
use app\models\Task;

class EvaluatorTemplateResource extends \app\models\Model implements IOpenApiFieldTypes
{
    // Common
    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $os;

    /**
     * @var string
     */
    public $image;

    // Auto Test

    /**
     * @var bool
     */
    public $autoTest;

    /**
     * @var string|null
     */
    public $appType;

    /**
     * @var string|null
     */
    public $compileInstructions;

    /**
     * @var string|null
     */
    public $runInstructions;

    // Static Code Analysis
    /**
     * @var bool
     */
    public $staticCodeAnalysis;

    /**
     * @var string|null
     */
    public $staticCodeAnalyzerTool;

    /**
     * @var string|null
     */
    public $staticCodeAnalyzerInstructions;

    /**
     * @var string|null
     */
    public $codeCheckerCompileInstructions;

    /**
     * @var string|null
     */
    public $codeCheckerToggles;

    /**
     * @var string|null
     */
    public $codeCheckerSkipFile;

    public function fieldTypes(): array
    {
        return [
            'name' => new OAProperty(['type' => 'string']),
            'os' => new OAProperty(['type' => 'string', 'enum' => new OAList(Task::TEST_OS)]),
            'image' => new OAProperty(['type' => 'string']),
            'autoTest' => new OAProperty(['type' => 'boolean']),
            'appType' => new OAProperty(['type' => 'string', 'nullable' => 'true']),
            'compileInstructions' => new OAProperty(['type' => 'string', 'nullable' => 'true']),
            'runInstructions' => new OAProperty(['type' => 'string', 'nullable' => 'true']),
            'staticCodeAnalysis' => new OAProperty(['type' => 'boolean']),
            'staticCodeAnalyzerTool' => new OAProperty(['type' => 'string', 'nullable' => 'true']),
            'staticCodeAnalyzerInstructions' => new OAProperty(['type' => 'string', 'nullable' => 'true']),
            'codeCheckerSkipFile' => new OAProperty(['type' => 'string', 'nullable' => 'true']),
            'codeCheckerCompileInstructions' => new OAProperty(['type' => 'string', 'nullable' => 'true']),
            'codeCheckerToggles' => new OAProperty(['type' => 'string', 'nullable' => 'true']),
        ];
    }
}
