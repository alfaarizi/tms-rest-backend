<?php

namespace app\tests\unit\fixtures;

use yii\test\ActiveFixture;

class TestInstanceQuestionFixture extends ActiveFixture
{
    public $modelClass = 'app\models\ExamTestInstanceQuestion';
    public $dataFile =  __DIR__ . '/../../_data/testinstancequestions.php';
    public $depends = [
        'app\tests\unit\fixtures\QuestionFixture',
        'app\tests\unit\fixtures\TestInstanceFixture',
    ];
}
