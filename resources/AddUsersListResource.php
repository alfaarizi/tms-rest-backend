<?php

namespace app\resources;

use app\components\openapi\generators\OAItems;
use app\components\openapi\generators\OAProperty;
use app\components\openapi\IOpenApiFieldTypes;
use app\models\Model;

/**
 * Class AddUsersListResource
 */
class AddUsersListResource extends Model implements IOpenApiFieldTypes
{
    /** @var string[] */
    public $userCodes = [];

    public function rules()
    {
        return [
            ['userCodes', 'required'],
            ['userCodes', 'each', 'rule' => ['string']],
        ];
    }

    public function fieldTypes(): array
    {
        return [
            'userCodes' =>  new OAProperty(['type' => 'array', new OAItems(['type' => 'string'])]),
        ];
    }
}
