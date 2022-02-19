<?php

namespace app\components\openapi\generators;

use app\components\openapi\exceptions\CodeGeneratorException;

/**
 * Generated OAItems annotations.
 */
class OAItems extends CodeGenerator
{
    public array $attributes;

    public function __construct($attributes)
    {
        $this->attributes = $attributes;
    }

    /**
     * @throws CodeGeneratorException
     */
    public function getCode(): string
    {
        return "@OA\Items({$this->generateAttributesCode($this->attributes, false)})";
    }
}
