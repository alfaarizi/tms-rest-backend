<?php

namespace app\modules\instructor\resources;

use app\components\openapi\generators\OAList;
use app\components\openapi\generators\OAItems;
use app\components\openapi\generators\OAProperty;
use app\components\openapi\IOpenApiFieldTypes;
use app\models\Model;
use app\models\Task;

class EvaluatorAdditionalInformationResource extends Model implements IOpenApiFieldTypes
{
    public $templates;
    public $osMap;
    public $appTypes;
    public $imageSuccessfullyBuilt;
    public $imageCreationDate;
    public $supportedStaticAnalyzers;

    public function fieldTypes(): array
    {
        return [
            'templates' => new OAProperty(
                [
                    'type' => 'array',
                    new OAItems(['ref' => '#/components/schemas/Instructor_EvaluatorTemplateResource_Read'])
                ]
            ),
            'osMap' => new OAProperty(['type' => 'string', 'enum' => new OAList(Task::TEST_OS)]),
            'appTypes' => new OAProperty(['type' => 'string', 'enum' => new OAList(Task::APP_TYPES)]),
            'imageSuccessfullyBuilt' => new OAProperty(['type' => 'boolean']),
            'imageCreationDate' => new OAProperty(['type' => 'string']),
            'supportedStaticAnalyzers' => new OAProperty(
                [
                    'type' => 'array',
                    new OAItems(['ref' => '#/components/schemas/Instructor_StaticAnalyzerToolResource_Read'])
                ]
            ),
        ];
    }
}
