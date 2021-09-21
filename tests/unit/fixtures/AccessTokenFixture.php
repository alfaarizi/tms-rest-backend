<?php

namespace app\tests\unit\fixtures;

use yii\test\ActiveFixture;

class AccessTokenFixture extends ActiveFixture
{
    public $modelClass = 'app\models\AccessToken';
    public $dataFile =  __DIR__ . '/../../_data/accesstokens.php';
    public $depends = [
        'app\tests\unit\fixtures\UserFixture'
    ];
}
