<?php

namespace app\tests\unit\fixtures;

use yii\test\ActiveFixture;

class TestInstanceFixture extends ActiveFixture
{
    public $modelClass = 'app\models\ExamTestInstance';
    public $dataFile =  __DIR__ . '/../../_data/testinstances.php';
    public $depends = [
        'app\tests\unit\fixtures\UserFixture',
        'app\tests\unit\fixtures\TestFixture',
        'app\tests\unit\fixtures\QuestionFixture',
        'app\tests\unit\fixtures\AnswerFixture',
    ];
}
