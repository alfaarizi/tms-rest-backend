<?php

namespace app\components\openapi\generators;

use app\components\openapi\exceptions\CodeGeneratorException;

/**
 * A base class for CodeGenerator classes
 */
abstract class CodeGenerator
{
    /**
     * Generates PHP code for the given object
     * @return string
     */
    abstract public function getCode(): string;

    /**
     * Generates a list separated with comas from a string array
     * @param array $items
     * @return string
     */
    protected function generateList(array $items): string
    {
        $code = "";

        foreach ($items as $item) {
            $code .= "$item,";
        }

        return substr($code, 0, -1);
    }

    /**
     * Generates attributes list for OA annotations
     * @param array $attributes List of attributes
     * @param bool $wrapRefs Wrap references with "allOf", so they won't override other attributes
     * @return string
     * @throws CodeGeneratorException
     */
    protected function generateAttributesCode(array $attributes, bool $wrapRefs): string
    {
        $items = [];

        foreach (array_keys($attributes) as $key) {
            if (is_int($key) && $attributes[$key] instanceof CodeGenerator) {
                $items[] = $attributes[$key]->getCode();
            } elseif (is_string($key)) {
                if ($wrapRefs && $key ===  "ref") {
                    // Wrap refs
                    $items[] = "oneOf={@OA\Schema(ref=" . $this->formatValue($attributes[$key]) . ")}";
                } elseif ($attributes[$key] instanceof CodeGenerator) {
                    $items[] = "{$key}=" . $attributes[$key]->getCode();
                } elseif (is_string($attributes[$key])) {
                    $items[] = "{$key}=" . $this->formatValue($attributes[$key]);
                } else {
                    throw new CodeGeneratorException(
                        "Invalid attribute type (" . gettype($attributes[$key]) . ") for key ($key)"
                    );
                }
            } else {
                throw new CodeGeneratorException(
                    "Invalid attribute type (" . gettype($attributes[$key]) . ") for key ($key)"
                );
            }
        }

        return $this->generateList($items);
    }

    /**
     * Creates a new comment line with the given indentation
     * @param string $content content of the new line
     * @param int $spaces number of spaces for indentation
     * @return string
     */
    protected function newCommentLine(string $content, int $spaces): string
    {
        return " *" . str_repeat(" ", $spaces) . $content . PHP_EOL;
    }

    /**
     * Displays values in the correct formats.
     * For example string must have quotes.
     * @param string $value
     * @return string
     */
    protected function formatValue(string $value): string
    {
        if ($value === "true" || $value === "false") {
            // Return boolean values without quotes
            return $value;
        } else {
            // Return string with quotes
            return '"' . $value . '"';
        }
    }
}
