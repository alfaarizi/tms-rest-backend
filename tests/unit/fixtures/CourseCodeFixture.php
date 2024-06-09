<?php

namespace app\tests\unit\fixtures;

use yii\test\ActiveFixture;

class CourseCodeFixture extends ActiveFixture
{
    public $modelClass = 'app\models\CourseCode';
    public $dataFile =  __DIR__ . '/../../_data/course_codes.php';
}
