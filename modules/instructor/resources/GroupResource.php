<?php

namespace app\modules\instructor\resources;

use app\components\openapi\generators\OAItems;
use app\components\openapi\generators\OAProperty;
use app\resources\CourseResource;
use app\resources\UserResource;
use Yii;
use yii\helpers\ArrayHelper;

/**
 * Resource class for module 'Group'
 */
class GroupResource extends \app\models\Group
{

    /**
     * @inheritdoc
     */
    public function fields()
    {
        return [
            'id',
            'number',
            'course',
            'isExamGroup',
            'semesterID',
            'canvasCanBeSynchronized',
            'isCanvasCourse',
            'timezone',
            'canvasUrl',
        ];
    }

    /**
     * @inheritdoc
     */
    public function extraFields()
    {
        return [
            'instructors',
            'students',
            'tasks',
        ];
    }

    public function fieldTypes(): array
    {
        return ArrayHelper::merge(
            parent::fieldTypes(),
            [
                'course' => new OAProperty(['ref' => '#/components/schemas/Common_CourseResource_Read']),
                'instructors' => new OAProperty(
                    [
                        'type' => 'array',
                        new OAItems(['ref' => '#/components/schemas/Common_UserResource_Read']),
                    ]
                ),
                'students' => new OAProperty(
                    [
                        'type' => 'array',
                        new OAItems(['ref' => '#/components/schemas/Common_UserResource_Read']),
                    ]
                ),
                'tasks' => new OAProperty(
                    [
                        'type' => 'array',
                        new OAItems(['ref' => '#/components/schemas/Instructor_TaskResource_Read']),
                    ]
                ),
            ]
        );
    }

    /**
     * @inheritdoc
     */
    public function getCourse()
    {
        return $this->hasOne(CourseResource::class, ['id' => 'courseID']);
    }

    /**
     * @inheritdoc
     */
    public function getInstructors()
    {
        return $this->hasMany(UserResource::class, ['id' => 'userID'])
            ->viaTable('{{%instructor_groups}}', ['groupID' => 'id']);
    }

    public function getStudents()
    {
        return $this->hasMany(UserResource::class, ['id' => 'userID'])
            ->viaTable('{{%subscriptions}}', ['groupID' => 'id', 'semesterID' => "semesterID"]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTasks()
    {
        return $this->hasMany(TaskResource::class, ['groupID' => 'id']);
    }
}
