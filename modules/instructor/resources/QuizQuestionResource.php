<?php

namespace app\modules\instructor\resources;

use app\components\openapi\generators\OAItems;
use app\components\openapi\generators\OAProperty;
use app\models\QuizAnswer;
use app\models\QuizQuestion;
use yii\helpers\ArrayHelper;

class QuizQuestionResource extends QuizQuestion
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

    public function fieldTypes(): array
    {
        return ArrayHelper::merge(
            parent::fieldTypes(),
            [
                'answers' => new OAProperty(
                    [
                        'type' => 'array',
                        new OAItems(['ref' => '#/components/schemas/Instructor_QuizAnswerResource_Read'])
                    ]
                ),
                'correctAnswers' => new OAProperty(
                    [
                        'type' => 'array',
                        new OAItems(['ref' => '#/components/schemas/Instructor_QuizAnswerResource_Read'])
                    ]
                ),
            ],
        );
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAnswers()
    {
        return $this->hasMany(QuizAnswerResource::class, ['questionID' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCorrectAnswers()
    {
        return $this
            ->hasMany(QuizAnswerResource::class, ['questionID' => 'id'])
            ->where(['correct' => true]);
    }
}
