<?php

namespace app\resources;

use app\components\openapi\generators\OAItems;
use app\components\openapi\generators\OAProperty;
use app\components\openapi\IOpenApiFieldTypes;

class UsersAddedResource extends \app\models\Model implements IOpenApiFieldTypes
{
    public $addedUsers;
    public $failed;

    public function fieldTypes(): array
    {
        return [
            'addedUsers' => new OAProperty(
                [
                    'type' => 'array',
                    new OAItems(['ref' => '#/components/schemas/Common_UserResource_Read'])
                ]
            ),
            'failed' => new OAProperty(
                [
                    'type' => 'array',
                    new OAItems(['ref' => '#/components/schemas/Common_UserAddErrorResource_Read'])
                ]
            ),
        ];
    }
}
