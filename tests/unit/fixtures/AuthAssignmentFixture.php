<?php

namespace app\tests\unit\fixtures;

use yii\test\ActiveFixture;

class AuthAssignmentFixture extends ActiveFixture
{
    public $tableName = '{{%auth_assignment}}';
    public $dataFile =  __DIR__ . '/../../_data/auth_assignments.php';
}
