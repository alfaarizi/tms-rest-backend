<?php

namespace app\modules\instructor\resources;

use app\models\ExamQuestionSet;
use app\resources\CourseResource;

class ExamQuestionSetResource extends ExamQuestionSet
{
    public function fields()
    {
        return [
            'id',
            'name',
            'course',
            'courseID'
        ];
    }

    public function extraFields()
    {
        return [
            'tests'
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCourse()
    {
        return $this->hasOne(CourseResource::class, ['id' => 'courseID']);
    }

    public function getTests()
    {
        return $this->hasMany(ExamTestResource::class, ['questionsetID' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getQuestions()
    {
        return $this->hasMany(ExamQuestionResource::class, ['questionsetID' => 'id']);
    }
}
