<?php

namespace app\tests\unit\fixtures;

use yii\test\ActiveFixture;

class TaskFixture extends ActiveFixture
{
    public $modelClass = 'app\models\Task';
    public $dataFile =  __DIR__ . '/../../_data/tasks.php';
    public $depends = [
        'app\tests\unit\fixtures\GroupFixture',
        'app\tests\unit\fixtures\UserFixture'
    ];
}
