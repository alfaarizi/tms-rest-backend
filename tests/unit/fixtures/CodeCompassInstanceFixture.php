<?php

namespace app\tests\unit\fixtures;

use yii\test\ActiveFixture;

class CodeCompassInstanceFixture extends ActiveFixture
{
    public $modelClass = 'app\models\CodeCompassInstance';
    public $dataFile =  __DIR__ . '/../../_data/codecompassinstances.php';
    public $depends = [
        'app\tests\unit\fixtures\UserFixture',
        'app\tests\unit\fixtures\StudentFilesFixture',
    ];
}
