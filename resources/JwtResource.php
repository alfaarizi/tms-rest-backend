<?php

namespace app\resources;

use app\components\openapi\generators\OAProperty;
use app\components\openapi\IOpenApiFieldTypes;
use app\models\Model;

class JwtResource extends Model implements IOpenApiFieldTypes
{
    public $token;

    public function fields(): array
    {
        return [
            'token'
        ];
    }

    public function rules(): array
    {
        return [
            ['token', 'string']
        ];
    }

    public function fieldTypes(): array
    {
        return [
            'token' => new OAProperty(['type' => 'string'])
        ];
    }
}
