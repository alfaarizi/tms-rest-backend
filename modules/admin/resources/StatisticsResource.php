<?php

namespace app\modules\admin\resources;

use app\components\openapi\generators\OAProperty;
use app\components\openapi\IOpenApiFieldTypes;
use app\models\Model;

class StatisticsResource extends Model implements IOpenApiFieldTypes
{
    public int $groupsCount;
    public int $tasksCount;
    public int $submissionsCount;
    public int $testedSubmissionCount;
    public int $groupsCountPerSemester;
    public int $tasksCountPerSemester;
    public int $submissionsCountPerSemester;
    public int $testedSubmissionCountPerSemester;
    public int $submissionsUnderTestingCount;
    public int $submissionsToBeTested;
    public ?float $diskFree;

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
            'groupsCountPerSemester',
            'tasksCountPerSemester',
            'submissionsCountPerSemester',
            'testedSubmissionCountPerSemester',
            'submissionsUnderTestingCount',
            'submissionsToBeTested',
            'diskFree'
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
            'groupsCountPerSemester' => new OAProperty(['type' => 'integer']),
            'tasksCountPerSemester' => new OAProperty(['type' => 'integer']),
            'submissionsCountPerSemester' => new OAProperty(['type' => 'integer']),
            'testedSubmissionCountPerSemester' => new OAProperty(['type' => 'integer']),
            'submissionsUnderTestingCount' => new OAProperty(['type' => 'integer']),
            'submissionsToBeTested' => new OAProperty(['type' => 'integer']),
            'diskFree' => new OAProperty(new OAProperty(['type' => 'number', 'format' => 'float'])),
        ];
    }
}
