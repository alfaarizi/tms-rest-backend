<?php

namespace app\modules\admin\resources;

use app\components\openapi\generators\OAItems;
use app\components\openapi\generators\OAProperty;
use app\components\openapi\IOpenApiFieldTypes;
use app\models\Model;

class CreateUpdateCourseResource extends Model implements IOpenApiFieldTypes
{
    public string $name;
    public array $codes;

    public function rules()
    {
        return [
            [['name', 'codes'], 'required'],
            ['name', 'string'],
        ];
    }

    public function fieldTypes(): array
    {
        return [
            'name' => new OAProperty(['type' => 'integer']),
            'codes' => new OAProperty(['type' => 'array', new OAItems(['type' => 'string'])]),
        ];
    }
}
