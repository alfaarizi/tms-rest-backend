<?php

namespace app\resources;

use app\components\openapi\generators\OAProperty;
use app\components\openapi\IOpenApiFieldTypes;
use app\models\Model;

class PrivateSystemInfoResource extends Model implements IOpenApiFieldTypes
{
    public int $uploadMaxFilesize;
    public int $postMaxSize;
    public bool $isAutoTestEnabled;
    public bool $isVersionControlEnabled;
    public bool $isCanvasEnabled;
    public bool $isCodeCompassEnabled;
    public ?SemesterResource $actualSemester;

    public function fields(): array
    {
        return [
            'uploadMaxFilesize',
            'postMaxSize',
            'isAutoTestEnabled',
            'isVersionControlEnabled',
            'isCanvasEnabled',
            'isCodeCompassEnabled',
            'actualSemester'
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
            'isAutoTestEnabled' => new OAProperty(['type' => 'boolean']),
            'isVersionControlEnabled' => new OAProperty(['type' => 'boolean']),
            'isCanvasEnabled' => new OAProperty(['type' => 'boolean']),
            'isCodeCompassEnabled' => new OAProperty(['type' => 'boolean']),
            'actualSemester' => new OAProperty(['ref' => '#/components/schemas/Common_SemesterResource_Read']),
        ];
    }
}
