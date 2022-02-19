<?php

namespace app\components\openapi\generators;

use app\components\openapi\exceptions\CodeGeneratorException;

/**
 * Generates OAProperty annotations
 */
class OAProperty extends CodeGenerator
{
    public array $attributes;
    public string $field;

    public function __construct($attributes)
    {
        $this->attributes = $attributes;
    }

    /**
     * @throws CodeGeneratorException
     */
    public function getCode(): string
    {
        if (empty($this->field)) {
            throw new CodeGeneratorException("Field name is empty for OAProperty");
        }
        $copy = $this->attributes;
        $copy['property'] = $this->field;
        return "@OA\Property({$this->generateAttributesCode($copy, true)})";
    }
}
