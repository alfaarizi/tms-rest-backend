<?php

namespace app\tests\unit\fixtures;

use yii\test\ActiveFixture;

class QuestionFixture extends ActiveFixture
{
    public $modelClass = 'app\models\QuizQuestion';
    public $dataFile =  __DIR__ . '/../../_data/questions.php';
    public $depends = ['app\tests\unit\fixtures\QuestionSetFixture'];
}
