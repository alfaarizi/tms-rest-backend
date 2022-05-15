<?php

namespace app\modules\instructor\resources;

use app\components\openapi\generators\OAProperty;
use app\components\openapi\IOpenApiFieldTypes;
use app\models\Model;

class SetupCodeCompassParserResource extends Model implements IOpenApiFieldTypes
{
    public string $codeCompassCompileInstructions;
    public string $codeCompassPackagesInstallInstructions;

    public function rules()
    {
        return [
            [['codeCompassPackagesInstallInstructions'], 'string', 'max' => 255],
            [['codeCompassCompileInstructions'], 'string', 'max' => 1000],
        ];
    }

    public function fieldTypes(): array
    {
        return [
            'codeCompassPackagesInstallInstructions' => new OAProperty(['type' => 'string']),
            'codeCompassCompileInstructions' => new OAProperty(['type' => 'string'])
        ];
    }
}
