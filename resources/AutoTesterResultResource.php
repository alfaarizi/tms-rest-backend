<?php

namespace app\resources;

use app\components\openapi\generators\OAProperty;
use app\components\openapi\IOpenApiFieldTypes;

class AutoTesterResultResource extends \app\models\Model implements IOpenApiFieldTypes
{
    public $testCaseNr;
    public $isPassed;
    public $errorMsg;

    public function __construct(int $testCaseNr = null, bool $isPassed = null, string $errorMsg = null)
    {
        parent::__construct();
        $this->testCaseNr = $testCaseNr;
        $this->isPassed = $isPassed;
        $this->errorMsg = $errorMsg;
    }

    /**
     * @inheritdoc
     */
    public function fields()
    {
        return [
            'testCaseNr',
            'errorMsg',
            'isPassed'
        ];
    }

    /**
     * @inheritdoc
     */
    public function extraFields()
    {
        return [];
    }

    public function fieldTypes(): array
    {
        return [
            'testCaseNr' => new OAProperty(['type' => 'integer']),
            'isPassed' => new OAProperty(['type' => 'boolean']),
            'errorMsg' => new OAProperty(['type' => 'string']),
        ];
    }
}
