<?php

namespace app\modules\instructor\resources;

use app\components\openapi\generators\OAItems;
use app\components\openapi\generators\OAProperty;
use app\components\openapi\IOpenApiFieldTypes;
use app\resources\SemesterResource;
use yii\helpers\ArrayHelper;

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

    public function fieldTypes(): array
    {
        return ArrayHelper::merge(
            parent::fieldTypes(),
            [
                'studentFiles' => new OAProperty(
                    [
                        'type' => 'array',
                        new OAItems(['ref' => '#/components/schemas/Instructor_StudentFileResource_Read'])
                    ]
                ),
                'instructorFiles' => new OAProperty(
                    [
                        'type' => 'array',
                        new OAItems(['ref' => '#/components/schemas/Instructor_InstructorFileResource_Read'])
                    ]
                ),
                'group' => new OAProperty(['ref' => '#/components/schemas/Instructor_GroupResource_Read']),
                'semester' => new OAProperty(['ref' => '#/components/schemas/Common_SemesterResource_Read']),
            ]
        );
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
