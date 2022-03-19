<?php

namespace app\modules\instructor\resources;

use app\components\openapi\generators\OAProperty;
use app\components\openapi\IOpenApiFieldTypes;
use app\models\Model;

/**
 * Class OAuth2Response
 * @property string $code code from canvas
 * @property string $state unique identifier for user trying to log in
 * @property string $error error message
 */
class OAuth2ResponseResource extends Model implements IOpenApiFieldTypes
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

    public function fieldTypes(): array
    {
        return [
            'code' => new OAProperty(['type' => 'string']),
            'state' => new OAProperty(['type' => 'string']),
            'error' => new OAProperty(['type' => 'string']),
        ];
    }
}
