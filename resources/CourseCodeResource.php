<?php

namespace app\resources;

class CourseCodeResource extends \app\models\CourseCode
{
    /**
     * @inheritdoc
     */
    public function fields()
    {
        return [
            'id',
            'courseId',
            'code',
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

        return $types;
    }
}
