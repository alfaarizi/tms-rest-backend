<?php

namespace app\tests\unit\fixtures;

use yii\test\ActiveFixture;

class SubmittedAnswerFixture extends ActiveFixture
{
    public $modelClass = 'app\models\ExamSubmittedAnswer';
    public $dataFile =  __DIR__ . '/../../_data/submittedanswers.php';
    public $depends = [
        'app\tests\unit\fixtures\AnswerFixture',
        'app\tests\unit\fixtures\TestInstanceFixture',
    ];
}
