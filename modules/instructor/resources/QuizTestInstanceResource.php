<?php

namespace app\modules\instructor\resources;

use app\components\openapi\generators\OAItems;
use app\components\openapi\generators\OAProperty;
use app\models\QuizQuestion;
use app\models\QuizTestInstance;
use app\resources\UserResource;
use yii\helpers\ArrayHelper;

class QuizTestInstanceResource extends QuizTestInstance
{
    public function fields()
    {
        return [
            'id',
            'score',
            'user',
            'testDuration'
        ];
    }

    public function extraFields()
    {
        return [
            'starttime',
            'finishtime',
            'submitted',
            'userID',
            'testID',
            'questions'
        ];
    }

    public function fieldTypes(): array
    {
        return ArrayHelper::merge(
            parent::fieldTypes(),
            [
                'userID' => new OAProperty(['ref' => '#/components/schemas/int_id']),
                'testID' => new OAProperty(['ref' => '#/components/schemas/int_id']),
                'testDuration' => new OAProperty(['type' => 'integer']),
                'questions' => new OAProperty(
                    [
                        'type' => 'array',
                        new OAItems(['ref' => '#/components/schemas/Instructor_QuizTestResource_Read'])]
                ),
                'user' => new OAProperty(
                    [
                        'type' => 'array',
                        new OAItems(['ref' => '#/components/schemas/Common_UserResource_Read'])
                    ]
                ),
            ]
        );
    }

    public function getUser()
    {
        return $this->hasOne(UserResource::class, ['id' => 'userID']);
    }

    public function getQuestions()
    {
        return $this->hasMany(QuizQuestionResource::class, ['id' => 'questionID'])
            ->viaTable('{{%quiz_testinstance_questions}}', ['testinstanceID' => 'id']);
    }
}
