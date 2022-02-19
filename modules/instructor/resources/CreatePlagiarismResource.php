<?php

namespace app\modules\instructor\resources;

use app\components\openapi\generators\OAItems;
use app\components\openapi\generators\OAProperty;
use app\components\openapi\IOpenApiFieldTypes;

class CreatePlagiarismResource extends \yii\base\Model implements IOpenApiFieldTypes
{
    public function rules()
    {
        return [
            [['name', 'selectedTasks', 'selectedStudents', 'ignoreThreshold'], 'required'],
            [['name', 'description'], 'string'],
            [['ignoreThreshold'], 'integer'],
            ['selectedTasks', 'each', 'rule' => ['integer']],
            ['selectedStudents', 'each', 'rule' => ['integer']],
        ];
    }

    public $name;
    public $description;
    public $selectedTasks;
    public $selectedStudents;
    public $ignoreThreshold;

    public function fieldTypes(): array
    {
        return [
            'name' => new OAProperty(['type' => 'string']),
            'description' => new OAProperty(['type' => 'string']),
            'selectedTasks' => new OAProperty(['type' => 'array', new OAItems(['type' => 'integer'])]),
            'selectedStudents' => new OAProperty(['type' => 'array', new OAItems(['type' => 'integer'])]),
            'ignoreThreshold' => new OAProperty(['type' => 'integer']),
        ];
    }
}
