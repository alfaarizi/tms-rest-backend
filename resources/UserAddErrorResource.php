<?php

namespace app\resources;

use app\components\openapi\generators\OAProperty;
use app\components\openapi\IOpenApiFieldTypes;
use app\models\Model;

/**
 * Stores a user code, error pair
 */
class UserAddErrorResource extends Model implements IOpenApiFieldTypes
{
    public $userCode;
    public $cause;

    /**
     * UserAddErrorResource constructor.
     * @param $userCode
     * @param $cause
     */
    public function __construct($userCode = null, $cause = null)
    {
        parent::__construct();
        $this->userCode = $userCode;
        $this->cause = $cause;
    }

    public function fieldTypes(): array
    {
        return [
            'userCode' => new OAProperty(['type' => 'string']),
            'cause' => new OAProperty(['type' => 'object']),
        ];
    }
}
