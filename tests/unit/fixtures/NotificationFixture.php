<?php

namespace app\tests\unit\fixtures;

use yii\test\ActiveFixture;

class NotificationFixture extends ActiveFixture
{
    public $modelClass = \app\models\Notification::class;
    public $dataFile =  __DIR__ . '/../../_data/notifications.php';
}
