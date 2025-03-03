<?php

namespace app\tests\unit\fixtures;

use yii\test\ActiveFixture;

class QuestionSetFixture extends ActiveFixture
{
    public $modelClass = 'app\models\QuizQuestionSet';
    public $dataFile =  __DIR__ . '/../../_data/questionsets.php';
    public $depends = [
        'app\tests\unit\fixtures\CourseFixture',
        'app\tests\unit\fixtures\GroupFixture',
    ];
}
