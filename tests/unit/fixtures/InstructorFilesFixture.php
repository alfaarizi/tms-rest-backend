<?php

namespace app\tests\unit\fixtures;

use yii\test\ActiveFixture;

class InstructorFilesFixture extends ActiveFixture
{
    public $modelClass = 'app\models\InstructorFile';
    public $dataFile =  __DIR__ . '/../../_data/instructorfiles.php';
    public $depends = [
        'app\tests\unit\fixtures\TaskFixture'
    ];
}
