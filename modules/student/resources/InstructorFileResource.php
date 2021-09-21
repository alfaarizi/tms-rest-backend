<?php

namespace app\modules\student\resources;

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
            'uploadTime'
        ];
    }

    public function extraFields()
    {
        return ['task'];
    }

    public function getTask()
    {
        return $this->hasOne(TaskResource::class, ['id' => 'taskID']);
    }
}
