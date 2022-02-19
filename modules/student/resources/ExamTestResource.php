<?php

namespace app\modules\student\resources;

use app\components\openapi\generators\OAProperty;
use yii\helpers\ArrayHelper;

class ExamTestResource extends \app\models\ExamTest
{
    public function fields()
    {
        return [
            'name',
            'availablefrom',
            'availableuntil',
            'duration',
            'groupNumber',
            'courseName'
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
                'courseName' => new OAProperty(['type' => 'string']),
                'groupNumber' => new OAProperty(['ref' => '#/components/schemas/int_id']),
            ]
        );
    }


    public function getGroupNumber()
    {
        return $this->group->number;
    }

    public function getCourseName()
    {
        return $this->group->course->name;
    }
}
