<?php

namespace app\modules\instructor\resources;

use app\models\PlagiarismBasefile;
use app\models\Task;
use app\models\User;
use app\components\openapi\generators\OAItems;
use app\components\openapi\generators\OAList;
use app\components\openapi\generators\OAProperty;
use app\components\openapi\IOpenApiFieldTypes;
use app\models\MossPlagiarism;
use app\models\Plagiarism;
use yii\validators\InlineValidator;

/**
 * @property-read string $ignoreFilesString
 */
class CreatePlagiarismResource extends \yii\base\Model implements IOpenApiFieldTypes
{
    public function rules()
    {
        return [
            // General rules
            [['name', 'selectedTasks', 'selectedStudents', 'type'], 'required'],
            [['name', 'description'], 'string'],
            [['type'], 'validateType'],
            [['selectedTasks', 'selectedStudents', 'selectedBasefiles'], 'each', 'rule' => ['integer']],
            [['selectedStudents'], 'validateMultipleValues'],
            [['selectedBasefiles'], 'default', 'value' => []],

            // Moss
            [['ignoreThreshold'], 'required', 'when' => static fn (CreatePlagiarismResource $model) => $model->type === MossPlagiarism::ID],
            [['ignoreThreshold'], 'integer'],

            // JPlag
            [['tune'], 'integer'],
            [['ignoreFiles'], 'each', 'rule' => ['match', 'pattern' => '~^[^\\n\\\\/]+$~']],
            [['ignoreFiles'], 'default', 'value' => []],

            // Expensive rules (require DB)
            [['selectedTasks'], 'exist', 'allowArray' => true, 'targetClass' => Task::class, 'targetAttribute' => 'id'],
            [['selectedStudents'], 'exist', 'allowArray' => true, 'targetClass' => User::class, 'targetAttribute' => 'id'],
            [['selectedBasefiles'], 'exist', 'allowArray' => true, 'targetClass' => PlagiarismBasefile::class, 'targetAttribute' => 'id'],
        ];
    }

    /** @var string */
    public $name;
    /** @var string */
    public $description;
    /** @var string */
    public $type;
    /** @var int[] */
    public $selectedTasks;
    /** @var int[] */
    public $selectedStudents;
    /** @var int[] */
    public $selectedBasefiles;
    /** @var int */
    public $ignoreThreshold;
    /** @var int */
    public $tune;
    /** @var string[] */
    public $ignoreFiles;

    public function validateType(string $attribute, $params, InlineValidator $validator)
    {
        $availableTypes = Plagiarism::getAvailableTypes();
        if (!in_array($this->$attribute, $availableTypes, true)) {
            if (empty($availableTypes)) {
                $validator->addError($this, $attribute, 'There are no configured plagiarism types on this server.');
            } else {
                $validator->addError($this, $attribute, 'Unsupported type. Supported plagiarism {0, plural, one{type} other{types}}: {1}', [
                    count($availableTypes),
                    implode(', ', $availableTypes),
                ]);
            }
        }
    }

    public function validateMultipleValues(string $attribute, $params, InlineValidator $validator)
    {
        if (count($this->$attribute) <= 1) {
            $validator->addError($this, $attribute, 'The {attribute} attribute should have multiple values.');
        }
    }

    public function getIgnoreFilesString(): string
    {
        return implode("\n", $this->ignoreFiles);
    }

    public function fieldTypes(): array
    {
        return [
            'name' => new OAProperty(['type' => 'string']),
            'description' => new OAProperty(['type' => 'string']),
            'type' => new OAProperty(['type' => 'string', 'enum' => new OAList(Plagiarism::POSSIBLE_TYPES)]),
            'selectedTasks' => new OAProperty(['type' => 'array', new OAItems(['ref' => '#/components/schemas/int_id'])]),
            'selectedStudents' => new OAProperty(['type' => 'array', new OAItems(['ref' => '#/components/schemas/int_id'])]),
            'selectedBasefiles' => new OAProperty(['type' => 'array', new OAItems(['ref' => '#/components/schemas/int_id'])]),
            'ignoreThreshold' => new OAProperty(['type' => 'integer']),
            'ignoreFiles' => new OAProperty(['type' => 'array', new OAItems(['type' => 'string'])]),
            'tune' => new OAProperty(['type' => 'integer']),
        ];
    }
}
