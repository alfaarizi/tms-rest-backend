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
    public $neptunCodes = [];

    public function rules()
    {
        return [
            ['neptunCodes', 'required'],
            ['neptunCodes', 'each', 'rule' => ['string']],
        ];
    }

    public function fieldTypes(): array
    {
        return [
            'neptunCodes' =>  new OAProperty(['type' => 'array', new OAItems(['type' => 'string'])]),
        ];
    }
}
