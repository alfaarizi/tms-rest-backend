<?php

namespace app\modules\instructor\resources;

use app\components\openapi\generators\OAProperty;
use app\models\QuizTest;
use yii\helpers\ArrayHelper;

/**
 * @property-read bool $finalized
 * @property-read QuizTestInstanceResource[] $testInstances
 */
class QuizTestResource extends QuizTest
{
    public function fields()
    {
        return [
            'id',
            'name',
            'questionamount',
            'duration',
            'shuffled',
            'unique',
            'availablefrom',
            'availableuntil',
            'password',
            'isPasswordProtected',
            'groupID',
            'questionsetID',
            'finalized',
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
                'group' => new OAProperty(['ref' => '#/components/schemas/Instructor_QuizTestResource_Read']),
                'finalized' => new OAProperty(['type' => 'bool']),
            ]
        );
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTestInstances()
    {
        return $this->hasMany(QuizTestInstanceResource::class, ['testID' => 'id']);
    }

    /**
     * @return bool
     */
    public function getFinalized()
    {
        return $this->getTestInstances()->exists();
    }

    public function getGroup(): \yii\db\ActiveQuery
    {
        return $this->hasOne(GroupResource::class, ['id' => 'groupID']);
    }
}
