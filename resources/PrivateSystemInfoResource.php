<?php

namespace app\resources;

use app\components\openapi\generators\OAProperty;
use app\components\openapi\IOpenApiFieldTypes;
use app\models\Model;

class PrivateSystemInfoResource extends Model implements IOpenApiFieldTypes
{
    public int $uploadMaxFilesize;
    public int $postMaxSize;

    public function fields(): array
    {
        return [
            'uploadMaxFilesize',
            'postMaxSize',
        ];
    }

    public function extraFields(): array
    {
        return [];
    }

    public function fieldTypes(): array
    {
        return [
            'uploadMaxFilesize' => new OAProperty(['type' => 'integer']),
            'postMaxSize' => new OAProperty(['type' => 'integer']),
        ];
    }
}
