<?php

namespace app\tests\unit\fixtures;

use yii\test\ActiveFixture;

class JPlagPlagiarismFixture extends ActiveFixture
{
    public $modelClass = \app\models\JPlagPlagiarism::class;
    public $dataFile =  __DIR__ . '/../../_data/jplag_plagiarism.php';
    public $depends = [
        \app\tests\unit\fixtures\PlagiarismFixture::class,
    ];
}
