<?php

namespace app\modules\student\resources;

use app\components\openapi\generators\OAProperty;
use yii\helpers\ArrayHelper;

/**
 * Resource class for module 'InstructorFile'
 */
class InstructorFileResource extends \app\models\InstructorFile
{
    public function fields()
    {
        return [
            'id',
            'name',
            'uploadTime',
            'category'
        ];
    }

    public function extraFields()
    {
        return ['task'];
    }

    public function fieldTypes(): array
    {
        return ArrayHelper::merge(
            parent::fieldTypes(),
            [
                'task' => new OAProperty(
                    [
                        'ref' => '#/components/schemas/Student_TaskResource_Read'
                    ]
                )
            ]
        );
    }

    public function getTask(): \yii\db\ActiveQuery
    {
        return $this->hasOne(TaskResource::class, ['id' => 'taskID']);
    }
}
