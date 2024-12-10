<?php

namespace app\modules\instructor\resources;

use app\components\openapi\generators\OAList;
use app\components\openapi\generators\OAItems;
use app\components\openapi\generators\OAProperty;
use app\components\openapi\IOpenApiFieldTypes;
use app\models\Model;
use app\models\Task;

class SetupEvaluatorEnvironmentResource extends Model implements IOpenApiFieldTypes
{
    public $testOS;
    public $imageName;
    public $files;

    public function rules(): array
    {
        return [
            [['testOS'], 'required'],
            [['testOS'], 'in', 'range' => Task::TEST_OS],
            [['imageName'], 'string', 'max' => 255],
            [['files'], 'file', 'skipOnEmpty' => true, 'maxFiles' => 20],
        ];
    }

    public function fieldTypes(): array
    {
        return [
            'testOS' => new OAProperty(['type' => 'string', 'enum' => new OAList(Task::TEST_OS)]),
            'imageName' => new OAProperty(['type' => 'string']),
            'files' => new OAProperty(['type' => 'array', new OAItems(['type' => 'string', 'format' => 'binary'])]),
        ];
    }
}
