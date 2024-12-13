<?php

namespace app\tests\unit\fixtures;

use yii\test\ActiveFixture;

class IpAddressFixture extends ActiveFixture
{
    public $modelClass = 'app\models\IpAddress';
    public $dataFile =  __DIR__ . '/../../_data/ipaddresses.php';
    public $depends = [
        'app\tests\unit\fixtures\SubmissionsFixture',
        'app\tests\unit\fixtures\UserFixture'
    ];
}
