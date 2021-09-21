<?php

namespace app\modules\student\resources;

use app\models\Group;
use app\resources\CourseResource;

/**
 * Resource class for module 'Group'
 */
class GroupResource extends Group
{
    /**
     * @inheritdoc
     */
    public function fields()
    {
        return [
            'id',
            'number' => function() {
                return !$this->isExamGroup ? $this->number : null;
            },
            'course',
            'instructorNames',
        ];
    }

    /**
     * @inheritdoc
     */
    public function extraFields()
    {
        return [];
    }

    /**
     * @return array|string[]
     */
    public function getInstructorNames()
    {
        if ($this->isExamGroup) {
            return [];
        }

        return array_map(function ($user) {
            return $user->name;
        }, $this->instructors);
    }

    /**
     * @inheritdoc
     */
    public function getCourse()
    {
        return $this->hasOne(CourseResource::class, ['id' => 'courseID']);
    }
}
