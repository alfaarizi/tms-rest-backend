<?php

namespace app\tests\unit\fixtures;

use yii\test\ActiveFixture;

class TestCaseFixture extends ActiveFixture
{
    public $modelClass = 'app\models\TestCase';
    public $dataFile =  __DIR__ . '/../../_data/testcases.php';
    public $depends = ['app\tests\unit\fixtures\TaskFixture'];
}
