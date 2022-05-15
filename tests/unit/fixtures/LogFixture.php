<?php

namespace app\tests\unit\fixtures;

use yii\test\ActiveFixture;

class LogFixture extends ActiveFixture
{
    public $modelClass = 'app\models\Log';
    public $dataFile =  __DIR__ . '/../../_data/logs.php';
    public $depends = [];
}
