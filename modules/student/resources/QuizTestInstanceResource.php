<?php

namespace app\modules\student\resources;

use app\components\openapi\generators\OAProperty;
use app\models\QuizTestInstance;
use yii\helpers\ArrayHelper;

class QuizTestInstanceResource extends QuizTestInstance
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
        ];
    }

    /**
     * @inheritdoc
     */
    public function extraFields()
    {
        return [];
    }

    public function fieldTypes(): array
    {
        return ArrayHelper::merge(
            parent::fieldTypes(),
            [
                'maxScore' => new OAProperty(['type' => 'integer']),
                'test' => new OAProperty(['ref' => '#/components/schemas/Instructor_QuizTestResource_Read']),
            ]
        );
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTest()
    {
        return $this->hasOne(QuizTestResource::class, ['id' => 'testID']);
    }

    /**
     * @return int
     */
    public function getMaxScore()
    {
        return (int)$this->getQuestions()->count();
    }
}
