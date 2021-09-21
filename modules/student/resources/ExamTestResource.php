<?php

namespace app\modules\student\resources;

class ExamTestResource extends \app\models\ExamTest
{
    public function fields()
    {
        return [
            'name',
            'availablefrom',
            'availableuntil',
            'duration',
            'groupNumber',
            'courseName'
        ];
    }

    public function extraFields()
    {
        return [];
    }

    public function getGroupNumber()
    {
        return $this->group->number;
    }

    public function getCourseName()
    {
        return $this->group->course->name;
    }
}
