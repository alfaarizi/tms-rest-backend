<?php

namespace app\modules\student\resources;

use app\components\openapi\generators\OAProperty;
use app\components\openapi\IOpenApiFieldTypes;
use app\models\QuizAnswer;
use app\models\QuizSubmittedAnswer;
use app\models\QuizTestInstanceQuestion;
use yii\helpers\ArrayHelper;

class QuizResultQuestionResource extends QuizTestInstanceQuestion implements IOpenApiFieldTypes
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

    public function fieldTypes(): array
    {
        return ArrayHelper::merge(
            parent::fieldTypes(),
            [
                'isCorrect' => new OAProperty(['type' => 'boolean']),
                'questionText' => new OAProperty(['type' => 'string']),
                'answerText' => new OAProperty(['type' => 'string']),
            ]
        );
    }

    /**
     * @return string
     */
    public function getQuestionText(): string
    {
        return $this->question->text;
    }

    /**
     * @return bool
     */
    public function getIsCorrect(): bool
    {
        // Get selected answer
        $query = QuizSubmittedAnswer::find()->where(["testinstanceID" => $this->testinstanceID])->select('answerID');
        // Check if is in the correct answers
        $result = $this->question->getCorrectAnswers()->select("correct")->where(['in', 'id', $query])->scalar();
        return (bool)$result;
    }

    public function getAnswerText(): string
    {
        $query = QuizSubmittedAnswer::find()->where(["testinstanceID" => $this->testinstanceID])->select('answerID');
        $text = QuizAnswer::find()->select("text")->where(['in', 'id', $query])->andWhere(["questionID" => $this->questionID])->scalar();
        return $text ? $text : "";
    }
}
