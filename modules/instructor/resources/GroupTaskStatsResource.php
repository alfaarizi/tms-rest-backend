<?php

namespace app\modules\instructor\resources;

use app\components\openapi\generators\OAItems;
use app\components\openapi\generators\OAProperty;
use app\components\openapi\IOpenApiFieldTypes;
use app\models\Model;

class GroupTaskStatsResource extends Model implements IOpenApiFieldTypes
{
    public $taskID;
    public $name;
    public $points;
    public $submitted;

    public function fieldTypes(): array
    {
        return [
            'taskID' => new OAProperty(['ref' => '#/components/schemas/int_id']),
            'name' => new OAProperty(['type' => 'string']),
            'points' => new OAProperty(['type' => 'array', new OAItems(['type' => 'integer'])]),
            'submitted' => new OAProperty(['ref' => '#/components/schemas/Instructor_GroupSubmittedStatsResource_Read'])
        ];
    }
}
