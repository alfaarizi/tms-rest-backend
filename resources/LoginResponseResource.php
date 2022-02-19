<?php

namespace app\resources;

use app\components\openapi\generators\OAProperty;
use app\components\openapi\IOpenApiFieldTypes;
use app\models\Model;

class LoginResponseResource extends Model implements IOpenApiFieldTypes
{
    public $accessToken;
    public $imageToken;
    public $userInfo;


    public function fields()
    {
        return ['accessToken', 'imageToken', 'userInfo'];
    }

    public function fieldTypes(): array
    {
        return [
            'accessToken' => new OAProperty(['type' => 'string']),
            'imageToken' => new OAProperty(['type' => 'string']),
            'userInfo' => new OAProperty(['ref' => '#/components/schemas/Common_UserInfoResource_Read']),
        ];
    }
}
