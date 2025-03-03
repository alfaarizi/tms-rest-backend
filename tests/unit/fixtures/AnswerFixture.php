<?php

namespace app\tests\unit\fixtures;

use yii\test\ActiveFixture;

class AnswerFixture extends ActiveFixture
{
    public $modelClass = 'app\models\QuizAnswer';
    public $dataFile =  __DIR__ . '/../../_data/answers.php';
    public $depends = ['app\tests\unit\fixtures\QuestionFixture'];
}
