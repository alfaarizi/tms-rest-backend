<?php

namespace app\tests\unit\fixtures;

use yii\test\ActiveFixture;

class InstructorGroupFixture extends ActiveFixture
{
    public $modelClass = 'app\models\InstructorGroup';
    public $dataFile =  __DIR__ . '/../../_data/instructorgroups.php';
}
