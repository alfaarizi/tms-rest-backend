<?php

namespace app\modules\instructor\resources;

use app\components\openapi\generators\OAItems;
use app\components\openapi\generators\OAProperty;
use app\components\openapi\IOpenApiFieldTypes;
use app\models\Model;

class InstructorFilesUploadResultResource extends Model implements IOpenApiFieldTypes
{
    public $uploaded;
    public $failed;

    public function fieldTypes(): array
    {
        return [
            'uploaded' => new OAProperty(
                [
                    'type' => 'array',
                    new OAItems(['ref' => '#/components/schemas/Instructor_InstructorFileResource_Read']),
                ]),
            'failed' => new OAProperty(
                [
                    'type' => 'array',
                    new OAItems(['ref' => '#/components/schemas/Instructor_UploadFailedResource_Read'])
                ],
            ),
        ];
    }
}
