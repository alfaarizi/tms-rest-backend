<?php

namespace app\modules\instructor\resources;

use app\components\openapi\generators\OAProperty;
use app\components\openapi\IOpenApiFieldTypes;
use yii\base\Model;

class TaskUpdateOptionsResource extends Model implements IOpenApiFieldTypes
{
    public bool $emailNotification;

    public function fields()
    {
        return [
            'emailNotification'
        ];
    }

    public function fieldTypes(): array
    {
        return [
            'emailNotification' => new OAProperty(['type' => 'boolean']),
        ];
    }

    public function rules(): array
    {
        return [
            [['emailNotification'], 'boolean'],
        ];
    }
}
