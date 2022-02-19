<?php

namespace app\modules\student\resources;

use app\components\openapi\generators\OAItems;
use app\components\openapi\generators\OAProperty;
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

    public function fieldTypes(): array
    {
        $types = parent::fieldTypes();

        $types['number'] = new OAProperty(['type' => 'integer', 'nullable' => 'true']);
        $types['course'] = new OAProperty(['ref' => '#/components/schemas/Common_CourseResource_Read']);
        $types['instructorNames'] = new OAProperty(['type' => 'array', new OAItems(['type' => 'string'])]);

        return $types;
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
