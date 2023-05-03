<?php

namespace app\tests\unit\fixtures;

use yii\test\ActiveFixture;

class MossPlagiarismFixture extends ActiveFixture
{
    public $modelClass = \app\models\MossPlagiarism::class;
    public $dataFile =  __DIR__ . '/../../_data/moss_plagiarism.php';
    public $depends = [
        \app\tests\unit\fixtures\PlagiarismFixture::class,
    ];
}
