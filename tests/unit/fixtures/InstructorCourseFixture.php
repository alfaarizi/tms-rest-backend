<?php

namespace app\tests\unit\fixtures;

use yii\test\ActiveFixture;

class InstructorCourseFixture extends ActiveFixture
{
    public $modelClass = 'app\models\InstructorCourse';
    public $dataFile =  __DIR__ . '/../../_data/instructorcourses.php';
}
