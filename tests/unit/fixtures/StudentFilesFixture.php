<?php

namespace app\tests\unit\fixtures;

use yii\test\ActiveFixture;

class StudentFilesFixture extends ActiveFixture
{
    public $modelClass = 'app\models\StudentFile';
    public $dataFile =  __DIR__ . '/../../_data/studentfiles.php';
    public $depends = [
        'app\tests\unit\fixtures\UserFixture',
        'app\tests\unit\fixtures\TaskFixture',
    ];
}
