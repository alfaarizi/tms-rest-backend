<?php

namespace app\modules\instructor\resources;

class QuizAnswerResource extends \app\models\QuizAnswer
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
