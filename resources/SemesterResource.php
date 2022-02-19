<?php

namespace app\resources;


use app\components\openapi\IOpenApiFieldTypes;
use app\components\openapi\generators\OAItems;
use app\components\openapi\generators\OAProperty;

/**
 * This is the resource class for model class "Semester".
 */
class SemesterResource extends \app\models\Semester implements IOpenApiFieldTypes
{
    /**
     * @inheritdoc
     */
    public function fields()
    {
        return [
            'id',
            'name',
            'actual'
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
            'id' => new OAProperty(['type' => 'integer']),
            'name' => new OAProperty(['type' => 'string']),
            'actual' => new OAProperty(['type' => 'integer']),
        ];
    }

}
