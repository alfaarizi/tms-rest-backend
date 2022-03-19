<?php

namespace app\modules\instructor\resources;

use app\components\openapi\generators\OAProperty;
use app\models\ExamTest;
use yii\helpers\ArrayHelper;

class ExamTestResource extends ExamTest
{
    public function fields()
    {
        return [
            'id',
            'name',
            'questionamount',
            'duration',
            'shuffled',
            'unique',
            'availablefrom',
            'availableuntil',
            'courseName',
            'groupNumber',
            'courseID',
            'groupID',
            'questionsetID',
            'timezone',
            'semesterID',
        ];
    }

    public function extraFields()
    {
        return [];
    }

    public function fieldTypes(): array
    {
        return ArrayHelper::merge(
            parent::fieldTypes(),
            [
                'courseName' => new OAProperty(['type' => 'string']),
                'groupNumber' => new OAProperty(['type' => 'integer']),
                'courseID' => new OAProperty(['ref' => '#/components/schemas/int_id']),
                'groupID' => new OAProperty(['ref' => '#/components/schemas/int_id']),
                'questionsetID' => new OAProperty(['ref' => '#/components/schemas/int_id']),
                'timezone' => new OAProperty(['type' => 'string']),
                'semesterID' => new OAProperty(['ref' => '#/components/schemas/int_id']),
            ]
        );
    }

    /**
     * @return string
     */
    public function getCourseName()
    {
        return $this->group->course->name;
    }

    /**
     * @return string
     */
    public function getCourseID()
    {
        return $this->group->courseID;
    }

    /**
     * @return int
     */
    public function getGroupNumber()
    {
        return $this->group->number;
    }

    /**
     * @return int
     */
    public function getSemesterID()
    {
        return $this->group->semesterID;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTestInstances()
    {
        return $this->hasMany(ExamTestInstanceResource::class, ['testID' => 'id']);
    }

    /**
     * Timezone of the group
     * @return string
     */
    public function getTimezone()
    {
        return $this->group->timezone;
    }
}
