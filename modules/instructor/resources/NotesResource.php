<?php

namespace app\modules\instructor\resources;

use app\components\openapi\generators\OAProperty;
use app\components\openapi\IOpenApiFieldTypes;
use app\models\Model;

class NotesResource extends Model implements IOpenApiFieldTypes
{
    public string $notes;

    public function fields(): array
    {
        return [
            'notes'
        ];
    }

    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [
            ['notes', 'string'],
        ];
    }

    public function extraFields(): array
    {
        return [];
    }

    public function fieldTypes(): array
    {
        return [
            'notes' => new OAProperty(['type' => 'string'])
        ];
    }
}
