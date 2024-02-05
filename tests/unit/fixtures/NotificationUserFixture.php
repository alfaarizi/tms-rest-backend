<?php

namespace app\tests\unit\fixtures;

use yii\test\ActiveFixture;

class NotificationUserFixture extends ActiveFixture
{
    public $modelClass = \app\models\NotificationUser::class;
    public $dataFile =  __DIR__ . '/../../_data/notificationusers.php';
}
