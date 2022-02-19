<?php

namespace app\components\openapi\generators;

use app\components\openapi\exceptions\CodeGeneratorException;
use JsonSchema\Validator;
use yii\validators\RequiredValidator;

/**
 * Generates annotated class from the given model
 */
class OASchema extends CodeGenerator
{
    /**
     * Model name
     * @var string
     */
    public string $definitionName;

    /**
     * Fields of the Yii2 Model
     * @var array
     */
    public array $fields;

    /**
     * Type annotations of the model fields
     * @var array
     */
    public array $fieldTypes;

    /**
     * Extra fields of the Yii2 Model
     * @var array
     */
    public array $extraFields;

    /**
     * Yii2 validators applied to the model
     * @var array
     */
    public array $validators;

    /**
     * @param string $definitionName
     * @param array $fields
     * @param array $fieldTypes
     * @param array $extraFields
     * @param array $validators
     */
    public function __construct(
        string $definitionName,
        array $fields,
        array $fieldTypes,
        array $extraFields,
        array $validators
    ) {
        $this->definitionName = $definitionName;
        $this->fields = $fields;
        $this->fieldTypes = $fieldTypes;
        $this->extraFields = $extraFields;
        $this->validators = $validators;
    }

    /**
     * Generates annotated fields from the fields and extra fields
     * @param array $fields The field array of the model
     * @param bool $nullable add nullable true to all properties
     * @param int $spaces number of spaces for indentation
     * @return string
     * @throws CodeGeneratorException
     */
    private function generateFieldAnnotations(array $fields, bool $nullable, int $spaces): string
    {
        $annotatedFields = "";
        foreach (array_keys($fields) as $key) {
            // Handle mixed arrays (associative and sequential)
            if (is_string($key)) {
                $field = $key;
            } else if (is_int($key)) {
                $field = $fields[$key];
            } else {
                throw new CodeGeneratorException("Invalid key type for fields array");
            }

            if (empty($this->fieldTypes[$field])) {
                throw new CodeGeneratorException("Missing field type for $field in schema $this->definitionName");
            }

            $this->fieldTypes[$field]->field = $field;
            if ($nullable) {
                $this->fieldTypes[$field]->attributes['nullable'] = 'true';
            }
            $annotatedFields .= $this->newCommentLine($this->fieldTypes[$field]->getCode()  . ',', $spaces);
        }

        return $annotatedFields;
    }

    /**
     * Generates the required fields array for the annotation
     * @param Validator[] $validators Yii2 validator classes
     * @param int $spaces number of spaces for indentation
     * @return string
     */
    private function generateRequired(array $validators, int $spaces): string
    {
        $requiredFields = [];
        foreach ($validators as $validator) {
            if ($validator instanceof RequiredValidator) {
                $attributes = $validator->attributeNames;

                foreach ($attributes as $attribute) {
                    $requiredFields[] = '"' . $attribute . '"';
                }
            }
        }

        return !empty($requiredFields)
            ? $this->newCommentLine("required={{$this->generateList($requiredFields)}},", 4)
            : "";
    }


    public function getCode(): string
    {
        $code = "<?php";
        $code .= PHP_EOL;
        $code .= PHP_EOL;
        $code .= "/**";
        $code .= PHP_EOL;
        $code .= $this->newCommentLine("@OA\Schema(", 1);
        $code .= $this->newCommentLine("schema=\"{$this->definitionName}\",", 4);
        $code .= $this->generateRequired($this->validators, 4);
        $code .= $this->generateFieldAnnotations($this->fields, false, 4);
        $code .= $this->generateFieldAnnotations($this->extraFields, true, 4);
        $code .= $this->newCommentLine(")", 1);
        $code .= "*/";
        $code .= PHP_EOL;

        return $code;
    }
}
