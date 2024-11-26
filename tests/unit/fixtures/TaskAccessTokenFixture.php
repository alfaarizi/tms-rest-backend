<?php

namespace app\tests\unit\fixtures;

use yii\test\ActiveFixture;

class TaskAccessTokenFixture extends ActiveFixture
{
    public $modelClass = 'app\models\TaskAccessTokens';
    public $dataFile =  __DIR__ . '/../../_data/taskaccesstokens.php';
    public $depends = [
        'app\tests\unit\fixtures\AccessTokenFixture'
    ];
}
