<?php

namespace app\resources;


/**
 * This is the resource class for model class "Semester".
 */
class SemesterResource extends \app\models\Semester
{
    /**
     * @inheritdoc
     */
    public function fields()
    {
        return [
            'id',
            'name',
            'actual'
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
