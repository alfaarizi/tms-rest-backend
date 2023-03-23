<?php

namespace app\modules\student\resources;

use app\components\openapi\generators\OAItems;
use app\components\openapi\generators\OAProperty;
use app\models\CodeCheckerResult;
use app\resources\CodeCheckerReportResource;
use yii\db\ActiveQuery;
use yii\helpers\ArrayHelper;

class CodeCheckerResultResource extends CodeCheckerResult
{
    public function fields(): array
    {
        return [
            'id',
            'status',
            'translatedStatus'
        ];
    }

    public function extraFields(): array
    {
        return [
            'codeCheckerReports',
        ];
    }

    public function fieldTypes(): array
    {
        return ArrayHelper::merge(parent::fieldTypes(), [
            'codeCheckerReports' => new OAProperty(
                [
                    'type' => 'array',
                    new OAItems(['type' => '#/components/schemas/Common_CodeCheckerReportResource_Read'])
                ],
            ),
        ]);
    }

    public function getCodeCheckerReports(): ActiveQuery
    {
        return $this->hasMany(CodeCheckerReportResource::class, ['resultID' => 'id']);
    }
}
