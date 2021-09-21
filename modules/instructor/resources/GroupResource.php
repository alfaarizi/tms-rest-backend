<?php

namespace app\modules\instructor\resources;

use app\resources\CourseResource;
use app\resources\UserResource;
use Yii;

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
            'isCanvasCourse'
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
