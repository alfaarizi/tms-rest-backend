<?php

namespace app\resources;

use app\components\openapi\generators\OAProperty;
use app\components\openapi\IOpenApiFieldTypes;
use app\models\Model;

/**
 * Stores a neptun code, error pair
 */
class UserAddErrorResource extends Model implements IOpenApiFieldTypes
{
    public $neptun;
    public $cause;

    /**
     * UserAddErrorResource constructor.
     * @param $neptun
     * @param $cause
     */
    public function __construct($neptun = null, $cause = null)
    {
        parent::__construct();
        $this->neptun = $neptun;
        $this->cause = $cause;
    }

    public function fieldTypes(): array
    {
        return [
            'neptun' => new OAProperty(['type' => 'string']),
            'cause' => new OAProperty(['type' => 'object']),
        ];
    }
}
