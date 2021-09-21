<?php

namespace app\modules\student\resources;

use app\models\ExamAnswer;
use app\models\ExamSubmittedAnswer;
use app\models\ExamTestInstanceQuestion;

class ExamResultQuestionResource extends ExamTestInstanceQuestion
{
    public function fields()
    {
        return [
            'questionID',
            'questionText',
            'isCorrect',
            'answerText'
        ];
    }

    public function extraFields()
    {
        return [];
    }

    /**
     * @return string
     */
    public function getQuestionText()
    {
        return $this->question->text;
    }

    /**
     * @return int
     */
    public function getIsCorrect()
    {
        // Get selected answer
        $query = ExamSubmittedAnswer::find()->where(["testinstanceID" => $this->testinstanceID])->select('answerID');
        // Check if is in the correct answers
        $result = $this->question->getCorrectAnswers()->select("correct")->where(['in', 'id', $query])->scalar();
        return (bool)$result;
    }

    public function getAnswerText()
    {
        $query = ExamSubmittedAnswer::find()->where(["testinstanceID" => $this->testinstanceID])->select('answerID');
        $text = ExamAnswer::find()->select("text")->where(['in', 'id', $query])->andWhere(["questionID" => $this->questionID])->scalar();
        return $text ? $text : "";
    }
}
