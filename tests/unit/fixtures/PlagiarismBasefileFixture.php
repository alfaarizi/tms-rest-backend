<?php

namespace app\tests\unit\fixtures;

use yii\test\ActiveFixture;

class PlagiarismBasefileFixture extends ActiveFixture
{
    public $modelClass = \app\models\PlagiarismBasefile::class;
    public $dataFile =  __DIR__ . '/../../_data/plagiarism_basefiles.php';
    public $depends = [
        \app\tests\unit\fixtures\GroupFixture::class,
        \app\tests\unit\fixtures\UserFixture::class,
    ];
}
