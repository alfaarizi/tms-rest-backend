<?php

namespace app\resources;

use app\components\openapi\generators\OAItems;
use app\components\openapi\generators\OAProperty;
use app\components\openapi\IOpenApiFieldTypes;

class UsersAddedResource extends \app\models\Model implements IOpenApiFieldTypes
{
    /** @var UserResource[] */
    public array $addedUsers;
    /** @var UserAddErrorResource[] */
    public array $failed;

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
