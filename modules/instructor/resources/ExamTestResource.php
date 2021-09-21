<?php

namespace app\modules\instructor\resources;

use app\models\ExamTest;

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
            'questionsetID'

        ];
    }

    public function extraFields()
    {
        return [];
    }

    /**
     * @return string
     */
    public function getCourseName() {
        return $this->group->course->name;
    }

    /**
     * @return string
     */
    public function getCourseID() {
        return $this->group->courseID;
    }

    /**
     * @return int
     */
    public function getGroupNumber() {
        return $this->group->number;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTestInstances()
    {
        return $this->hasMany(ExamTestInstanceResource::class, ['testID' => 'id']);
    }
}
