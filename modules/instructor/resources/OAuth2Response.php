<?php

namespace app\modules\instructor\resources;

use app\models\Model;

/**
 * Class OAuth2Response
 * @property string $code code from canvas
 * @property string $state unique identifier for user trying to log in
 * @property string $error error message
 */
class OAuth2Response extends Model
{
    public $code;
    public $state;
    public $error;

    public function rules()
    {
        return [
            [['code', 'state', 'error'], 'string'],
            [['code', 'state'], 'required'],
        ];
    }
}
