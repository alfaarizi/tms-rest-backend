<?php

namespace app\tests\unit\fixtures;

use yii\test\ActiveFixture;

class SubmissionsFixture extends ActiveFixture
{
    public $modelClass = 'app\models\Submission';
    public $dataFile = __DIR__ . '/../../_data/submission.php';
    public $depends = [
        'app\tests\unit\fixtures\UserFixture',
        'app\tests\unit\fixtures\TaskFixture',
    ];
}
