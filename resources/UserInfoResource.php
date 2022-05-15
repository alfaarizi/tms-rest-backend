<?php

namespace app\resources;

use app\components\openapi\generators\OAProperty;
use app\components\openapi\IOpenApiFieldTypes;
use app\models\Model;

/**
 * Model LoginResponse
 * Contains the access token and other useful information about the user for the client
 *
 * @property int id
 * @property string $neptun
 * @property string $locale
 * @property bool $isStudent
 * @property bool $isFaculty
 * @property bool $isAdmin
 * @property SemesterResource $actualSemester
 * @property bool $isAutoTestEnabled;
 * @property bool $isVersionControlEnabled;
 * @property bool $isCanvasEnabled;
 * @property bool $isCodeCompassEnabled
 */
class UserInfoResource extends Model implements IOpenApiFieldTypes
{
    public $id;
    public $neptun;
    public $locale;
    public $isStudent;
    public $isFaculty;
    public $isAdmin;
    public $actualSemester;
    public $isAutoTestEnabled;
    public $isVersionControlEnabled;
    public $isCanvasEnabled;
    public $isCodeCompassEnabled;

    /**
     * @return string[]
     */
    public function fields()
    {
        return [
            'id',
            'neptun',
            'locale',
            'isStudent',
            'isFaculty',
            'isAdmin',
            'actualSemester',
            'isAutoTestEnabled',
            'isVersionControlEnabled',
            'isCanvasEnabled',
            'isCodeCompassEnabled'
        ];
    }

    /**
     * @return array
     */
    public function extraFields()
    {
        return [];
    }

    public function fieldTypes(): array
    {
        return [
            'id' => new OAProperty(['ref' => '#/components/schemas/int_id']),
            'neptun' => new OAProperty(['type' => 'string']),
            'locale' => new OAProperty(['type' => 'string']),
            'isStudent' => new OAProperty(['type' => 'boolean']),
            'isFaculty' => new OAProperty(['type' => 'boolean']),
            'isAdmin' => new OAProperty(['type' => 'boolean']),
            'actualSemester' => new OAProperty(['ref' => '#/components/schemas/Common_SemesterResource_Read']),
            'isAutoTestEnabled' => new OAProperty(['type' => 'boolean']),
            'isVersionControlEnabled' => new OAProperty(['type' => 'boolean']),
            'isCanvasEnabled' => new OAProperty(['type' => 'boolean']),
            'isCodeCompassEnabled' => new OAProperty(['type' => 'boolean']),
        ];
    }
}
