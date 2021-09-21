<?php

namespace app\tests\unit\fixtures;

use yii\test\ActiveFixture;

class SemesterFixture extends ActiveFixture
{
    public $modelClass = 'app\models\Semester';
    public $dataFile =  __DIR__ . '/../../_data/semesters.php';
}
