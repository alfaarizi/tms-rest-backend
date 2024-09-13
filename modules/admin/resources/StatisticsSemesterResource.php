<?php

namespace app\modules\admin\resources;

use app\components\openapi\generators\OAProperty;
use app\components\openapi\IOpenApiFieldTypes;
use app\models\Model;

class StatisticsSemesterResource extends Model implements IOpenApiFieldTypes
{
    public int $groupsCount;
    public int $tasksCount;
    public int $submissionsCount;
    public int $testedSubmissionCount;

    /**
     * @inheritdoc
     */
    public function fields()
    {
        return [
            'groupsCount',
            'tasksCount',
            'submissionsCount',
            'testedSubmissionCount',
        ];
    }

    /**
     * @inheritdoc
     */
    public function fieldTypes(): array
    {
        return [
            'groupsCount' => new OAProperty(['type' => 'integer']),
            'tasksCount' => new OAProperty(['type' => 'integer']),
            'submissionsCount' => new OAProperty(['type' => 'integer']),
            'testedSubmissionCount' => new OAProperty(['type' => 'integer']),
        ];
    }
}
