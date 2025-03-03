<?php

namespace app\tests\unit\fixtures;

use yii\test\ActiveFixture;

class TestFixture extends ActiveFixture
{
    public $modelClass = 'app\models\QuizTest';
    public $dataFile =  __DIR__ . '/../../_data/tests.php';
    public $depends = [
        'app\tests\unit\fixtures\QuestionSetFixture',
        'app\tests\unit\fixtures\GroupFixture',
        'app\tests\unit\fixtures\QuestionFixture',
        'app\tests\unit\fixtures\AnswerFixture',
        'app\tests\unit\fixtures\SubscriptionFixture',
    ];
}
