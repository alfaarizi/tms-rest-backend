<?php

namespace app\tests\unit;

use app\models\QuizTestInstanceQuestion;
use app\models\QuizTestInstance;
use app\models\QuizQuestion;
use app\tests\unit\fixtures\TestInstanceQuestionFixture;
use UnitTester;

class TestInstanceQuestionTest extends \Codeception\Test\Unit
{
    protected UnitTester $tester;

    public function _fixtures()
    {
        return [
            'testinstancequestions' => [
                'class' => TestInstanceQuestionFixture::class
            ]
        ];
    }

    public function testValidateWithoutParams()
    {
        $testInstanceQuestion = new QuizTestInstanceQuestion();
        $this->assertFalse($testInstanceQuestion->validate(), "TestInstanceQuestion created without parameters should not be valid.");
    }

    public function testValidateCorrectModel()
    {
        $testInstanceQuestion = new QuizTestInstanceQuestion();
        $testInstanceQuestion->testinstanceID = 2;
        $testInstanceQuestion->questionID = 3;
        $this->assertTrue($testInstanceQuestion->validate(), "TestInstanceQuestion created with parameters should be valid.");
    }

    public function testGetters()
    {
        $this->assertNotNull(QuizTestInstance::findOne(1));
        $this->assertNotNull(QuizQuestion::findOne(1));
        $testInstanceQuestion = new QuizTestInstanceQuestion();
        $testInstanceQuestion->testinstanceID = 1;
        $testInstanceQuestion->questionID = 1;
        $this->assertNotNull($testInstanceQuestion->getQuestion(), "Should return the related question");
        $this->assertNotNull($testInstanceQuestion->getTestInstance(), "Should return the related test instance");
    }

    public function testSaveQuestionTwice()
    {
        $this->assertNotNull(QuizTestInstanceQuestion::find()->where(["questionID" => 1, "testinstanceID" => 1]));
        $question = new QuizTestInstanceQuestion();
        $question->testinstanceID = 1;
        $question->questionID = 1;
        $this->assertFalse($question->save(), "Same question should not be present twice in the same test instance");
    }

    public function testDeleteQuestion()
    {
        $this->assertNotNull(QuizQuestion::findOne(1));
        $question = new QuizTestInstanceQuestion();
        $question->testinstanceID = 1;
        $question->questionID = 1;
        $question->save();
        $this->tester->expectException(\yii\db\IntegrityException::class, function () {
                QuizQuestion::findOne(1)->delete();
        });
    }

    public function testDeleteTestInstance()
    {
        $this->assertNotNull(QuizTestInstance::findOne(1));
        $question = new QuizTestInstanceQuestion();
        $question->testinstanceID = 1;
        $question->questionID = 1;
        $question->save();
        $this->assertFalse(
            QuizTestInstance::findOne(1)->delete()
        );
    }
}
