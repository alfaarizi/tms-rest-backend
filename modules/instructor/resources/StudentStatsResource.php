<?php

namespace app\modules\instructor\resources;

use app\components\openapi\generators\OAItems;
use app\components\openapi\generators\OAProperty;
use app\components\openapi\IOpenApiFieldTypes;
use app\models\Model;

class StudentStatsResource extends Model implements IOpenApiFieldTypes
{
    public $taskID;
    public $name;
    public $submittingTime;
    public $softDeadLine;
    public $hardDeadLine;
    public $user;
    public $username;
    public $group;


    public function fieldTypes(): array
    {
        return [
            'taskID' => new OAProperty(['ref' => '#/components/schemas/int_id']),
            'name' => new OAProperty(['type' => 'string']),
            'submittingTime' => new OAProperty(['type' => 'string']),
            'softDeadLine' => new OAProperty(['type' => 'string']),
            'hardDeadLine' => new OAProperty(['type' => 'string']),
            'user' => new OAProperty(['type' => 'integer', 'nullable' => 'true']),
            'username' => new OAProperty(['type' => 'string']),
            'group' => new OAProperty(['type' => 'array', new OAItems(['type' => 'number'])]),
        ];
    }
}
