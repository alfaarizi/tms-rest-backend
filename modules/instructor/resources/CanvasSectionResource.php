<?php

namespace app\modules\instructor\resources;

use app\components\openapi\generators\OAProperty;
use app\components\openapi\IOpenApiFieldTypes;

class CanvasSectionResource extends \app\models\Model implements IOpenApiFieldTypes
{
    public $id;
    public $name;
    public $totalStudents;

    public function fieldTypes(): array
    {
        return [
            'id' => new OAProperty(['ref' => '#/components/schemas/int_id']),
            'name' => new OAProperty(['type' => 'string']),
            'totalStudents' => new OAProperty(['type' => 'integer']),
        ];
    }
}
