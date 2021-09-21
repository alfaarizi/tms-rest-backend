<?php

namespace app\modules\instructor\resources;

use app\models\ExamQuestion;
use app\models\ExamTestInstance;
use app\resources\UserResource;

class ExamTestInstanceResource extends ExamTestInstance
{
    public function fields()
    {
        return [
            'id',
            'score',
            'user',
            'testDuration'
        ];
    }

    public function extraFields()
    {
        return [
            'starttime',
            'finishtime',
            'submitted',
            'userID',
            'testID',
            'questions'
        ];
    }

    public function getUser()
    {
        return $this->hasOne(UserResource::class, ['id' => 'userID']);
    }

    public function getQuestions()
    {
        return $this->hasMany(ExamQuestionResource::class, ['id' => 'questionID'])
            ->viaTable('{{%exam_testinstance_questions}}', ['testinstanceID' => 'id']);
    }
}
