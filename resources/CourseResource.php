<?php

namespace app\resources;

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
            'code'
        ];
    }

    /**
     * @inheritdoc
     */
    public function extraFields()
    {
        return [];
    }
}
