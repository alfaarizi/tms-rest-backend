<?php

namespace app\resources;

use app\components\openapi\generators\OAProperty;
use app\components\openapi\IOpenApiFieldTypes;
use app\models\Model;

class PrivateSystemInfoResource extends Model implements IOpenApiFieldTypes
{
    public int $uploadMaxFilesize;
    public int $postMaxSize;
    public int $maxWebAppRunTime;
    public bool $isAutoTestEnabled;
    public bool $isVersionControlEnabled;
    public bool $isCanvasEnabled;
    public bool $isCodeCompassEnabled;
    public string $userCodeFormat;
    public string $serverDateTime;
    public ?SemesterResource $actualSemester;

    public function fields(): array
    {
        return [
            'uploadMaxFilesize',
            'postMaxSize',
            'maxWebAppRunTime',
            'isAutoTestEnabled',
            'isVersionControlEnabled',
            'isCanvasEnabled',
            'isCodeCompassEnabled',
            'userCodeFormat',
            'serverDateTime',
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
            'maxWebAppRunTime' => new OAProperty(['type' => 'integer']),
            'isAutoTestEnabled' => new OAProperty(['type' => 'boolean']),
            'isVersionControlEnabled' => new OAProperty(['type' => 'boolean']),
            'isCanvasEnabled' => new OAProperty(['type' => 'boolean']),
            'isCodeCompassEnabled' => new OAProperty(['type' => 'boolean']),
            'userCodeFormat' => new OAProperty(['type' => 'string']),
            'serverDateTime' => new OAProperty(['type' => 'string']),
            'actualSemester' => new OAProperty(['ref' => '#/components/schemas/Common_SemesterResource_Read']),
        ];
    }
}
