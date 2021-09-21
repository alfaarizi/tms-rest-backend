<?php

namespace app\tests\unit\fixtures;

use yii\test\ActiveFixture;

class CourseFixture extends ActiveFixture
{
    public $modelClass = 'app\models\Course';
    public $dataFile =  __DIR__ . '/../../_data/courses.php';
}
