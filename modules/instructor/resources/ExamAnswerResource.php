<?php

namespace app\modules\instructor\resources;

class ExamAnswerResource extends \app\models\ExamAnswer
{
    public function fields()
    {
        return [
            'id',
            'text',
            'correct',
            'questionID'
        ];
    }

    public function extraFields()
    {
        return [];
    }
}
