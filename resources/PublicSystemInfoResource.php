<?php

namespace app\resources;

use app\components\openapi\generators\OAProperty;
use app\components\openapi\IOpenApiFieldTypes;
use app\models\Model;

class PublicSystemInfoResource extends Model implements IOpenApiFieldTypes
{
    public string $version;

    public function fields(): array
    {
        return [
            'version'
        ];
    }

    public function extraFields(): array
    {
        return [];
    }

    public function fieldTypes(): array
    {
        return [
            'version' => new OAProperty(['type' => 'string'])
        ];
    }
}
