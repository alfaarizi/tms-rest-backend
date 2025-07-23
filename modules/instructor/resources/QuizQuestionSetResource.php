<?php

namespace app\modules\instructor\resources;

use app\components\openapi\generators\OAItems;
use app\components\openapi\generators\OAProperty;
use app\models\QuizQuestionSet;
use app\resources\CourseResource;
use yii\helpers\ArrayHelper;

class QuizQuestionSetResource extends QuizQuestionSet
{
    public function fields()
    {
        return [
            'id',
            'name',
            'course',
            'courseID'
        ];
    }

    public function extraFields()
    {
        return [
            'tests'
        ];
    }

    public function fieldTypes(): array
    {
        return ArrayHelper::merge(
            parent::fieldTypes(),
            [
                'course' => new OAProperty(['ref' => '#/components/schemas/Common_CourseResource_Read']),
                'tests' => new OAProperty(
                    [
                        'type' => 'array',
                        new OAItems(['ref' => '#/components/schemas/Instructor_QuizTestResource_Read'])
                    ]
                ),
            ]
        );
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCourse()
    {
        return $this->hasOne(CourseResource::class, ['id' => 'courseID']);
    }

    public function getTests()
    {
        return $this->hasMany(QuizTestResource::class, ['questionsetID' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getQuestions()
    {
        return $this->hasMany(QuizQuestionResource::class, ['questionsetID' => 'id'])->orderBy(['questionNumber' => SORT_ASC]);
    }
}
