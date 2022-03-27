<?php

namespace app\modules\instructor\resources;

use app\components\openapi\generators\OAList;
use app\components\openapi\generators\OAItems;
use app\components\openapi\generators\OAProperty;
use app\components\openapi\IOpenApiFieldTypes;
use app\models\Model;
use app\models\Task;

class SetupAutoTesterResource extends Model implements IOpenApiFieldTypes
{
    public $testOS;
    public $imageName;
    public $compileInstructions;
    public $runInstructions;
    public $showFullErrorMsg;
    public $reevaluateAutoTest;
    public $files;
    public $appType;
    public $port;

    public function rules()
    {
        return [
            [['testOS', 'appType'], 'required'],
            [['testOS'], 'string'],
            [['imageName'], 'string', 'max' => 255],
            [['compileInstructions', 'runInstructions'], 'string'],
            [['showFullErrorMsg'], 'boolean'],
            [['reevaluateAutoTest'], 'boolean'],
            [['files'], 'file', 'skipOnEmpty' => true, 'maxFiles' => 20],
            [['appType'], 'string'],
            [['port'], 'integer']
        ];
    }

    public function fieldTypes(): array
    {
        return [
            'testOS' => new OAProperty(['type' => 'string', 'enum' => new OAList(Task::TEST_OS)]),
            'imageName' => new OAProperty(['type' => 'string']),
            'compileInstructions' => new OAProperty(['type' => 'string']),
            'runInstructions' => new OAProperty(['type' => 'string']),
            'showFullErrorMsg' => new OAProperty(['type' => 'integer']),
            'reevaluateAutoTest' => new OAProperty(['type' => 'integer']),
            'files' => new OAProperty(['type' => 'array', new OAItems(['type' => 'string', 'format' => 'binary'])]),
            'appType' => new OAProperty(['type' => 'string', 'enum' => new OAList(Task::APP_TYPES)]),
            'port' => new OAProperty(['type' => 'integer']),
        ];
    }
}
