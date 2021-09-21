<?php

namespace app\modules\instructor\resources;

use app\resources\SemesterResource;

/**
 * Resource class for module 'Task'
 */
class TaskResource extends \app\models\Task
{
    public function fields()
    {
        return [
            'id',
            'name',
            'category',
            'translatedCategory',
            'description',
            'softDeadline',
            'hardDeadline',
            'available',
            'autoTest',
            'isVersionControlled',
            'groupID',
            'semesterID',
            'creatorName',
            'testOS',
            'showFullErrorMsg',
            'imageName',
            'compileInstructions',
            'runInstructions',
        ];
    }

    public function extraFields()
    {
        return [
            'studentFiles',
            'instructorFiles',
            'group',
            'semester'
        ];
    }

    public function getInstructorFiles()
    {
        return InstructorFileResource::find()->where(['taskID' => $this->id])->andOnCondition(
            [
                'not',
                ['name' => 'Dockerfile']
            ]
        );
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStudentFiles()
    {
        return $this->hasMany(StudentFileResource::class, ['taskID' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getGroup()
    {
        return $this->hasOne(GroupResource::class, ['id' => 'groupID']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSemester()
    {
        return $this->hasOne(SemesterResource::class, ['id' => 'semesterID']);
    }
}
