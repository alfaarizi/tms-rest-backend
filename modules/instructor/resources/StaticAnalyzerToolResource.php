<?php

namespace app\modules\instructor\resources;

use app\components\openapi\generators\OAProperty;
use app\components\openapi\IOpenApiFieldTypes;
use app\models\Model;

class StaticAnalyzerToolResource extends Model implements IOpenApiFieldTypes
{

    /**
     * @var string
     */
    public $name;
    /**
     * @var string
     */
    public $title;
    /**
     * @var
     */
    public $outputPath;


    public function fieldTypes(): array
    {
        return [
            'name' => new OAProperty(['type' => 'string']),
            'title' => new OAProperty(['type' => 'string']),
            'outputPath' => new OAProperty(['type' => 'string']),
        ];
    }
}
