<?php

namespace app\modules\instructor\resources;

use app\components\openapi\generators\OAList;
use app\components\openapi\generators\OAProperty;
use app\components\openapi\IOpenApiFieldTypes;
use app\models\Model;
use app\models\Task;

class SetupAutoTesterResource extends Model implements IOpenApiFieldTypes
{
    /**
     * @var bool
     */
    public $autoTest;

    /**
     * @var string
     */
    public $compileInstructions;

    /**
     * @var string
     */
    public $runInstructions;

    /**
     * @var bool
     */
    public $showFullErrorMsg;

    /**
     * @var bool
     */
    public $reevaluateAutoTest;

    /**
     * @var string
     */
    public $appType;

    /**
     * @var integer
     */
    public $port;

    public function rules()
    {
        return [
            [['appType', 'autoTest', 'showFullErrorMsg', 'reevaluateAutoTest'], 'required'],
            [['compileInstructions', 'runInstructions'], 'string'],
            [['autoTest', 'showFullErrorMsg', 'reevaluateAutoTest'], 'boolean'],
            [['appType'], 'string'],
            [['port'], 'integer']
        ];
    }

    public function fieldTypes(): array
    {
        return [
            'autoTest' => new OAProperty(['type' => 'boolean']),
            'compileInstructions' => new OAProperty(['type' => 'string']),
            'runInstructions' => new OAProperty(['type' => 'string']),
            'showFullErrorMsg' => new OAProperty(['type' => 'integer']),
            'reevaluateAutoTest' => new OAProperty(['type' => 'integer']),
            'appType' => new OAProperty(['type' => 'string', 'enum' => new OAList(Task::APP_TYPES)]),
            'port' => new OAProperty(['type' => 'integer']),
        ];
    }
}
