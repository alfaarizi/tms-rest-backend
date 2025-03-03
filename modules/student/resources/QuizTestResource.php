<?php

namespace app\modules\student\resources;

use app\components\openapi\generators\OAProperty;
use yii\helpers\ArrayHelper;

class QuizTestResource extends \app\models\QuizTest
{
    public function fields()
    {
        return [
            'name',
            'availablefrom',
            'availableuntil',
            'duration',
            'groupID'
        ];
    }

    public function extraFields()
    {
        return ['group'];
    }

    public function fieldTypes(): array
    {
        return ArrayHelper::merge(
            parent::fieldTypes(),
            [
                'group' => new OAProperty(['ref'=> '#/components/schemas/Student_QuizTestResource_Read']),
            ]
        );
    }

    public function getGroup(): \yii\db\ActiveQuery
    {
        return $this->hasOne(GroupResource::class, ['id' => 'groupID']);
    }
}
