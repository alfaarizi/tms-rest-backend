<?php

namespace app\modules\instructor\resources;

use app\components\openapi\generators\OAProperty;
use app\components\openapi\IOpenApiFieldTypes;
use app\models\Model;

class CanvasSetupResource extends Model implements IOpenApiFieldTypes
{
    public $canvasCourse;
    public $canvasSection;

    public function rules()
    {
        return [
            [['canvasCourse', 'canvasSection'], 'integer'],
            [['canvasCourse', 'canvasSection'], 'required'],
        ];
    }

    public function fieldTypes(): array
    {
        return [
            'canvasCourse' => new OAProperty(['type' => 'integer']),
            'canvasSection' => new OAProperty(['type' => 'integer']),
        ];
    }
}
