<?php

namespace app\tests\unit\fixtures;

use yii\test\ActiveFixture;

class PlagiarismFixture extends ActiveFixture
{
    public $modelClass = 'app\models\Plagiarism';
    public $dataFile =  __DIR__ . '/../../_data/plagiarism.php';
    public $depends = [
        'app\tests\unit\fixtures\UserFixture',
        'app\tests\unit\fixtures\SemesterFixture'
    ];
}
