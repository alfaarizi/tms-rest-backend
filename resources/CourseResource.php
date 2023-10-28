<?php

namespace app\resources;

use app\components\openapi\generators\OAItems;
use app\components\openapi\generators\OAProperty;

/**
 * Resource class for module 'Course'
 */
class CourseResource extends \app\models\Course
{
    /**
     * @inheritdoc
     */
    public function fields()
    {
        return [
            'id',
            'name',
            'code',
            'lecturerNames'
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

        $types['lecturerNames'] = new OAProperty(['type' => 'array', new OAItems(['type' => 'string'])]);

        return $types;
    }

    /**
     * @return array|string[]
     */
    public function getLecturerNames()
    {
        return array_map(function ($user) {
            return $user->name;
        }, $this->lecturers);
    }
}
