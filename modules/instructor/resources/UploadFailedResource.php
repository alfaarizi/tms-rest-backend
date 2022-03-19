<?php

namespace app\modules\instructor\resources;

use app\components\openapi\generators\OAProperty;
use app\components\openapi\IOpenApiFieldTypes;
use yii\base\Model;

class UploadFailedResource extends Model implements IOpenApiFieldTypes
{
    public $name;
    public $cause;

    public function fieldTypes(): array
    {
        return [
            'name' => new OAProperty(['type' => 'string']),
            'cause' => new OAProperty(['type' => 'object'])
        ];
    }
}
