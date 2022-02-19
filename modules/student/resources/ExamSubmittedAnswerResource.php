<?php

namespace app\modules\student\resources;

use app\models\ExamSubmittedAnswer;

class ExamSubmittedAnswerResource extends ExamSubmittedAnswer
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
