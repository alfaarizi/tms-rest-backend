<?php

namespace app\modules\instructor\resources;

use app\models\PlagiarismBasefile;
use app\models\Task;
use app\models\User;
use app\components\openapi\generators\OAItems;
use app\components\openapi\generators\OAProperty;
use app\components\openapi\IOpenApiFieldTypes;
use Yii;
use yii\validators\InlineValidator;

class CreatePlagiarismResource extends \yii\base\Model implements IOpenApiFieldTypes
{
    public function rules()
    {
        return [
            [['name', 'selectedTasks', 'selectedStudents', 'ignoreThreshold'], 'required'],
            [['name', 'description'], 'string'],
            [['ignoreThreshold'], 'integer'],
            [['selectedTasks', 'selectedStudents', 'selectedBasefiles'], 'each', 'rule' => ['integer']],
            [['selectedStudents'], 'validateMultipleValues'],
            [['selectedBasefiles'], 'default', 'value' => []],
            [['selectedTasks'], 'exist', 'allowArray' => true, 'targetClass' => Task::class, 'targetAttribute' => 'id'],
            [['selectedStudents'], 'exist', 'allowArray' => true, 'targetClass' => User::class, 'targetAttribute' => 'id'],
            [['selectedBasefiles'], 'exist', 'allowArray' => true, 'targetClass' => PlagiarismBasefile::class, 'targetAttribute' => 'id'],
        ];
    }

    /** @var string */
    public $name;
    /** @var string */
    public $description;
    /** @var int[] */
    public $selectedTasks;
    /** @var int[] */
    public $selectedStudents;
    /** @var int[] */
    public $selectedBasefiles;
    /** @var int */
    public $ignoreThreshold;

    public function validateMultipleValues(string $attribute, $params, InlineValidator $validator)
    {
        if (count($this->$attribute) <= 1) {
            $validator->addError($this, $attribute, 'The {attribute} attribute should have multiple values.');
        }
    }

    public function fieldTypes(): array
    {
        return [
            'name' => new OAProperty(['type' => 'string']),
            'description' => new OAProperty(['type' => 'string']),
            'selectedTasks' => new OAProperty(['type' => 'array', new OAItems(['ref' => '#/components/schemas/int_id'])]),
            'selectedStudents' => new OAProperty(['type' => 'array', new OAItems(['ref' => '#/components/schemas/int_id'])]),
            'selectedBasefiles' => new OAProperty(['type' => 'array', new OAItems(['ref' => '#/components/schemas/int_id'])]),
            'ignoreThreshold' => new OAProperty(['type' => 'integer']),
        ];
    }
}
