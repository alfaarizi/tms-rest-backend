<?php

namespace app\modules\instructor\resources;

use app\models\ExamAnswer;
use app\models\ExamQuestion;

class ExamQuestionResource extends ExamQuestion
{
    public function fields()
    {
        return [
            'id',
            'text',
            'questionsetID'
        ];
    }

    public function extraFields()
    {
        return [
            'answers',
            'correctAnswers'
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAnswers()
    {
        return $this->hasMany(ExamAnswerResource::class, ['questionID' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCorrectAnswers()
    {
        return $this
            ->hasMany(ExamAnswerResource::class, ['questionID' => 'id'])
            ->where(['correct' => true]);
    }
}
