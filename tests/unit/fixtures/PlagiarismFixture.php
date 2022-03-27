<?php

namespace app\tests\unit\fixtures;

use yii\test\ActiveFixture;

class PlagiarismFixture extends ActiveFixture
{
    public $modelClass = \app\models\Plagiarism::class;
    public $dataFile =  __DIR__ . '/../../_data/plagiarism.php';
    public $depends = [
        \app\tests\unit\fixtures\UserFixture::class,
        \app\tests\unit\fixtures\SemesterFixture::class,
        \app\tests\unit\fixtures\TaskFixture::class,
        \app\tests\unit\fixtures\PlagiarismBasefileFixture::class,
    ];
}
