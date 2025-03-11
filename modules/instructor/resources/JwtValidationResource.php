<?php

namespace app\modules\instructor\resources;

use app\components\openapi\generators\OAItems;
use app\components\openapi\generators\OAProperty;
use app\components\openapi\IOpenApiFieldTypes;
use app\models\Model;

class JwtValidationResource extends Model implements IOpenApiFieldTypes
{
    public bool $success;
    public array $payload;
    public string $message;

    public function fields(): array
    {
        return [
            'success',
            'payload',
            'message'
        ];
    }

    public function rules(): array
    {
        return [
            ['success', 'boolean'],
            ['message', 'string'],
        ];
    }

    public function fieldTypes(): array
    {
        return [
            'success' => new OAProperty(['type' => 'boolean']),
            'payload' => new OAProperty(['type' => 'array', new OAItems(['type' => 'string'])]),
            'message' => new OAProperty(['type' => 'string'])
        ];
    }
}
