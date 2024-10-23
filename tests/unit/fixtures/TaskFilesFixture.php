<?php

namespace app\tests\unit\fixtures;

use yii\test\ActiveFixture;

class TaskFilesFixture extends ActiveFixture
{
    public $modelClass = 'app\models\TaskFile';
    public $dataFile = __DIR__ . '/../../_data/taskfiles.php';
    public $depends = [
        'app\tests\unit\fixtures\TaskFixture'
    ];
}
