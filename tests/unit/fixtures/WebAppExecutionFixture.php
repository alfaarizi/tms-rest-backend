<?php

namespace app\tests\unit\fixtures;

use yii\test\ActiveFixture;

class WebAppExecutionFixture extends ActiveFixture
{
    public $modelClass = 'app\models\WebAppExecution';
    public $dataFile =  __DIR__ . '/../../_data/webAppExecutions.php';
    public $depends = [
        'app\tests\unit\fixtures\UserFixture',
        'app\tests\unit\fixtures\SubmissionsFixture',
    ];
}
