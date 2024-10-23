<?php

namespace app\tests\unit\fixtures;

use yii\test\ActiveFixture;

class CodeCheckerResultFixture extends ActiveFixture
{
    public $modelClass = 'app\models\CodeCheckerResult';
    public $dataFile =  __DIR__ . '/../../_data/codecheckerresults.php';
    public $depends = [
        'app\tests\unit\fixtures\SubmissionsFixture',
    ];
}
