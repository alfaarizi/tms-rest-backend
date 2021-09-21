<?php

namespace app\tests\unit\fixtures;

use yii\test\ActiveFixture;

class SubscriptionFixture extends ActiveFixture
{
    public $modelClass = 'app\models\Subscription';
    public $dataFile =  __DIR__ . '/../../_data/subscriptions.php';
    public $depends = [
        'app\tests\unit\fixtures\UserFixture',
        'app\tests\unit\fixtures\GroupFixture',
        'app\tests\unit\fixtures\SemesterFixture',
    ];
}
