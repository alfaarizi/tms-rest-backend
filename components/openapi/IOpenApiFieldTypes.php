<?php

namespace app\components\openapi;

/**
 * The IOpenApiFieldTypes interface indicates if OA\Schema annotation should be generated for the given class
 */
interface IOpenApiFieldTypes
{
    /**
     * OpenAPI types for fields
     * @return array
     */
    public function fieldTypes(): array;
}
