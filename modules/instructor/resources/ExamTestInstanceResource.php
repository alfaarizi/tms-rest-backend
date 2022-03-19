<?php

namespace app\modules\instructor\resources;

use app\components\openapi\generators\OAItems;
use app\components\openapi\generators\OAProperty;
use app\models\ExamQuestion;
use app\models\ExamTestInstance;
use app\resources\UserResource;
use yii\helpers\ArrayHelper;

class ExamTestInstanceResource extends ExamTestInstance
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
                        new OAItems(['ref' => '#/components/schemas/Instructor_ExamTestResource_Read'])]
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
        return $this->hasMany(ExamQuestionResource::class, ['id' => 'questionID'])
            ->viaTable('{{%exam_testinstance_questions}}', ['testinstanceID' => 'id']);
    }
}
