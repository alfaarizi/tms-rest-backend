<?php

namespace app\modules\instructor\resources;

use app\components\openapi\generators\OAList;
use app\components\openapi\generators\OAItems;
use app\components\openapi\generators\OAProperty;
use app\components\openapi\IOpenApiFieldTypes;
use app\models\Model;
use app\models\Task;

class TesterFormDataResource extends Model implements IOpenApiFieldTypes
{
    public $templates;
    public $osMap;
    public $imageSuccessfullyBuilt;

    public function fieldTypes(): array
    {
        return [
            'templates' => new OAProperty(
                [
                    'type' => 'array',
                    new OAItems(['ref' => '#/components/schemas/Instructor_TesterTemplateResource_Read'])
                ]
            ),
            'osMap' => new OAProperty(['type' => 'string', 'enum' => new OAList(Task::TEST_OS)]),
            'imageSuccessfullyBuilt' => new OAProperty(['type' => 'boolean']),
        ];
    }
}
