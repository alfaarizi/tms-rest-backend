<?php

namespace app\resources;

use app\components\openapi\generators\OAItems;
use app\components\openapi\generators\OAProperty;
use app\components\openapi\IOpenApiFieldTypes;
use yii\base\Model;

/**
 * This resource class represents the `ids` parameter in various
 * actions, which are actually getters, but use POST requests to
 * work around the URL length limitations of browsers.
 */
class IntIDListResource extends Model implements IOpenApiFieldTypes
{
    /** @var int[] */
    public array $ids;

    public function rules()
    {
        return [
            [['ids'], 'required', 'isEmpty' => 'is_null'], // empty array is okay
            [['ids'], 'each', 'rule' => ['integer'], 'isEmpty' => 'is_null'],
        ];
    }

    public function fieldTypes(): array
    {
        return [
            'ids' =>  new OAProperty(['type' => 'array', new OAItems(['ref' => '#/components/schemas/int_id'])]),
        ];
    }
}
