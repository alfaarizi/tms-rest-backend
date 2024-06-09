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
            'codes',
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

        $types['codes'] = new OAProperty(['type' => 'array', new OAItems(['type' => 'string'])]);
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

    /**
     * @return array|string[]
     */
    public function getCodes()
    {
        return array_map(function ($courseCode) {
            return $courseCode->code;
        }, $this->courseCodes);
    }
}
