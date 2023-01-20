<?php

namespace app\modules\instructor\resources;

use app\components\openapi\generators\OAList;
use app\components\openapi\generators\OAProperty;
use app\components\openapi\IOpenApiFieldTypes;
use app\models\Task;

class EvaluatorTemplateResource extends \app\models\Model implements IOpenApiFieldTypes
{
    public $name;
    public $os;
    public $image;
    public $autoTest;
    public $appType;
    public $compileInstructions;
    public $runInstructions;

    public function fieldTypes(): array
    {
        return [
            'name' => new OAProperty(['type' => 'string']),
            'os' => new OAProperty(['type' => 'string', 'enum' => new OAList(Task::TEST_OS)]),
            'image' => new OAProperty(['type' => 'string']),
            'autoTest' => new OAProperty(['type' => 'boolean']),
            'appType' => new OAProperty(['type' => 'string']),
            'compileInstructions' => new OAProperty(['type' => 'string']),
            'runInstructions' => new OAProperty(['type' => 'string']),
        ];
    }
}
