<?php

namespace app\resources;

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
 */
class UserInfoResource extends Model
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
}
