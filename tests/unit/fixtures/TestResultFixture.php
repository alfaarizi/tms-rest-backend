<?php

namespace app\tests\unit\fixtures;

use yii\test\ActiveFixture;

class TestResultFixture extends ActiveFixture
{
    public $modelClass = 'app\models\TestResult';
    public $dataFile =  __DIR__ . '/../../_data/testresults.php';
    public $depends = [
        'app\tests\unit\fixtures\StudentFilesFixture',
        'app\tests\unit\fixtures\TestCaseFixture',
    ];
}
