<?php

namespace app\modules\instructor\resources;

use app\components\openapi\generators\OAList;
use app\components\openapi\generators\OAProperty;
use app\components\openapi\IOpenApiFieldTypes;
use app\models\Group;
use app\models\Model;
use app\validators\CanvasSyncLevelValidator;

class CanvasSetupResource extends Model implements IOpenApiFieldTypes
{
    public int $canvasCourse;
    public int $canvasSection;
    public array $syncLevel;

    public function rules()
    {
        return [
            [['canvasCourse', 'canvasSection'], 'integer'],
            ['syncLevel', CanvasSyncLevelValidator::class],
            [['canvasCourse', 'canvasSection', 'syncLevel'], 'required'],
        ];
    }

    public function fieldTypes(): array
    {
        return [
            'canvasCourse' => new OAProperty(['type' => 'integer']),
            'canvasSection' => new OAProperty(['type' => 'integer']),
            'syncLevel' => new OAProperty(['type' => 'array', 'items' => new OAList(Group::SYNC_LEVEL_VALUES)]),
        ];
    }
}
