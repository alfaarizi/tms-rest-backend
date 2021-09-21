<?php


namespace app\resources;

use app\models\Model;

/**
 * Class AddUsersListResource
 */
class AddUsersListResource extends Model
{
    public $neptunCodes = [];

    public function rules()
    {
        return [
            ['neptunCodes', 'required'],
            ['neptunCodes', 'each', 'rule' => ['string']],
        ];
    }
}
