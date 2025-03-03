<?php

namespace app\modules\student\resources;

use app\models\QuizSubmittedAnswer;

class QuizSubmittedAnswerResource extends QuizSubmittedAnswer
{
    public function fields(): array
    {
        return [
            'testinstanceID',
            'answerID'
        ];
    }

    public function extraFields(): array
    {
        return [];
    }
}
