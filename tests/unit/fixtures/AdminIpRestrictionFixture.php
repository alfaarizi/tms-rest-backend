<?php

namespace app\tests\unit\fixtures;

use yii\test\ActiveFixture;

class AdminIpRestrictionFixture extends ActiveFixture
{
    public $modelClass = 'app\models\IpRestriction';
    public $dataFile =  __DIR__ . '/../../_data/ip_restrictions.php';
}
