<?php

namespace app\modules\instructor\resources;

use app\components\openapi\generators\OAProperty;
use app\components\openapi\IOpenApiFieldTypes;
use app\models\Model;

class GroupSubmittedStatsResource extends Model implements IOpenApiFieldTypes
{
    public $intime;
    public $delayed;
    public $missed;

    public function fieldTypes(): array
    {
        return [
            'intime' => new OAProperty(['type' => 'integer']),
            'delayed' => new OAProperty(['type' => 'integer']),
            'missed' => new OAProperty(['type' => 'integer']),
        ];
    }
}
