<?php

namespace app\tests\unit;

use app\models\ExamTestInstanceQuestion;
use app\models\ExamTestInstance;
use app\models\ExamQuestion;
use app\tests\unit\fixtures\TestInstanceQuestionFixture;

class TestInstanceQuestionTest extends \Codeception\Test\Unit
{
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
        $testInstanceQuestion = new ExamTestInstanceQuestion();
        $this->assertFalse($testInstanceQuestion->validate(), "TestInstanceQuestion created without parameters should not be valid.");
    }

    public function testValidateCorrectModel()
    {
        $testInstanceQuestion = new ExamTestInstanceQuestion();
        $testInstanceQuestion->testinstanceID = 2;
        $testInstanceQuestion->questionID = 3;
        $this->assertTrue($testInstanceQuestion->validate(), "TestInstanceQuestion created with parameters should be valid.");
    }

    public function testGetters()
    {
        $this->assertNotNull(ExamTestInstance::findOne(1));
        $this->assertNotNull(ExamQuestion::findOne(1));
        $testInstanceQuestion = new ExamTestInstanceQuestion();
        $testInstanceQuestion->testinstanceID = 1;
        $testInstanceQuestion->questionID = 1;
        $this->assertNotNull($testInstanceQuestion->getQuestion(), "Should return the related question");
        $this->assertNotNull($testInstanceQuestion->getTestInstance(), "Should return the related test instance");
    }

    public function testSaveQuestionTwice()
    {
        $this->assertNotNull(ExamTestInstanceQuestion::find()->where(["questionID" => 1, "testinstanceID" => 1]));
        $question = new ExamTestInstanceQuestion();
        $question->testinstanceID = 1;
        $question->questionID = 1;
        $this->assertFalse($question->save(), "Same question should not be present twice in the same test instance");
    }

    public function testDeleteQuestion()
    {
        $this->assertNotNull(ExamQuestion::findOne(1));
        $question = new ExamTestInstanceQuestion();
        $question->testinstanceID = 1;
        $question->questionID = 1;
        $question->save();
        $this->tester->expectException(\yii\db\IntegrityException::class, function () {
                ExamQuestion::findOne(1)->delete();
        });
    }

    public function testDeleteTestInstance()
    {
        $this->assertNotNull(ExamTestInstance::findOne(1));
        $question = new ExamTestInstanceQuestion();
        $question->testinstanceID = 1;
        $question->questionID = 1;
        $question->save();
        $this->tester->expectException(\yii\db\IntegrityException::class, function () {
                ExamTestInstance::findOne(1)->delete();
        });
    }
}
