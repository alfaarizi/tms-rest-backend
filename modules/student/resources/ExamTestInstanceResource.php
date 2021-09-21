<?php

namespace app\modules\student\resources;

use app\models\ExamTestInstance;

class ExamTestInstanceResource extends ExamTestInstance
{
    /**
     * @inheritdoc
     */
    public function fields()
    {
        return [
            'id',
            'starttime',
            'finishtime',
            'submitted',
            'score',
            'maxScore',
            'test',
            'submitted'
        ];
    }

    /**
     * @inheritdoc
     */
    public function extraFields()
    {
        return [];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTest()
    {
        return $this->hasOne(ExamTestResource::class, ['id' => 'testID']);
    }

    /**
     * @return int
     */
    public function getMaxScore()
    {
        return (int)$this->getQuestions()->count();
    }
}
