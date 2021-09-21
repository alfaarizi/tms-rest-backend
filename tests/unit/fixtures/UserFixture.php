<?php

namespace app\tests\unit\fixtures;

use yii\test\ActiveFixture;

class UserFixture extends ActiveFixture
{
    public $modelClass = 'app\models\User';
    public $dataFile =  __DIR__ . '/../../_data/users.php';
    public $depends = [
        'app\tests\unit\fixtures\AuthAssignmentFixture',
        'app\tests\unit\fixtures\InstructorGroupFixture',
    ];
}
