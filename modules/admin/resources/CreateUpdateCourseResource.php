<?php

namespace app\modules\admin\resources;

use app\components\openapi\generators\OAItems;
use app\components\openapi\generators\OAProperty;
use app\components\openapi\IOpenApiFieldTypes;
use app\models\Model;
use Yii;

class CreateUpdateCourseResource extends Model implements IOpenApiFieldTypes
{
    public string $name = '';
    public array $codes = [];
    public ?array $lecturerUserCodes = [];

    public const SCENARIO_CREATE = 'create';
    public const SCENARIO_UPDATE = 'update';

    public function scenarios(): array
    {
        return [
            self::SCENARIO_CREATE => ['name', 'codes', 'lecturerUserCodes'],
            self::SCENARIO_UPDATE => ['name', 'codes'],
        ];
    }

    public function rules(): array
    {
        return [
            [['name', 'codes', 'lecturerUserCodes'], 'required'],
            ['name', 'string'],
            ['lecturerUserCodes', 'each', 'rule' => ['string']],
        ];
    }

    public function fieldTypes(): array
    {
        return [
            'name' => new OAProperty(['type' => 'integer']),
            'codes' => new OAProperty(['type' => 'array', new OAItems(['type' => 'string'])]),
            'lecturerUserCodes' => new OAProperty(['type' => 'array', new OAItems(['ref' => 'string'])]),
        ];
    }
}
