<?php

namespace app\components\openapi\generators;

/**
 * Generates list of items for OpenAPI annotations.
 */
class OAList extends CodeGenerator
{
    /**
     * @var array
     */
    private $items;

    /**
     * @param array $items
     */
    public function __construct(array $items)
    {
        $this->items = $items;
    }

    public function getCode(): string
    {
        // Add quotes to all items
        $quotedItems = array_map(function ($item) {
            return '"' . $item . '"';
        }, $this->items);
        return "{{$this->generateList($quotedItems)}}";
    }
}
