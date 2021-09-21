<?php

namespace app\modules\instructor\resources;

class InstructorFileResource extends \app\models\InstructorFile
{
    public function fields()
    {
        return [
            'id',
            'name',
            'uploadTime'
        ];
    }

    public function extraFields()
    {
        return ['task'];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTask()
    {
        return $this->hasOne(TaskResource::class, ['id' => 'taskID']);
    }
}
