<?php

namespace app\tests\unit;

use app\models\QuizSubmittedAnswer;
use app\models\QuizTestInstance;
use app\models\QuizAnswer;
use app\tests\unit\fixtures\SubmittedAnswerFixture;
use app\tests\unit\fixtures\TestInstanceQuestionFixture;
use UnitTester;

class SubmittedAnswerTest extends \Codeception\Test\Unit
{
    protected UnitTester $tester;

    public function _fixtures()
    {
        return [
            'submittedanswers' => [
                'class' => SubmittedAnswerFixture::class
            ],
            'testinstancequestions' => [
                'class' => TestInstanceQuestionFixture::class
            ],
        ];
    }

    public function testValidateWithoutParams()
    {
        $submittedAnswer = new QuizSubmittedAnswer();
        $this->assertFalse($submittedAnswer->validate(), "Submitted answer created without parameters should not be valid.");
    }

    public function testValidateCorrectModel()
    {
        $submittedAnswer = new QuizSubmittedAnswer();
        $submittedAnswer->testinstanceID = 2;
        $submittedAnswer->answerID = 1;
        $this->assertTrue($submittedAnswer->validate(), "Submitted answer created with parameters should be valid.");
    }

    public function testValidateWithoutAnswerId()
    {
        $submittedAnswer = new QuizSubmittedAnswer();
        $submittedAnswer->testinstanceID = 1;
        $this->assertTrue($submittedAnswer->validate(), "Submitted answer created with parameters should be valid.");
    }

    public function testGetters()
    {
        $submittedAnswer = new QuizSubmittedAnswer();
        $submittedAnswer->testinstanceID = 1;
        $submittedAnswer->answerID = 1;
        $this->assertNotNull($submittedAnswer->getAnswer());
        $this->assertNotNull($submittedAnswer->getTestInstance());
    }

    public function testSubmitAnswerTwice()
    {
        $this->assertNotNull(QuizSubmittedAnswer::find()->where(["testinstanceID" => 1])->andWhere(["answerID" => 1])->one());
        $answer = new QuizSubmittedAnswer();
        $answer->testinstanceID = 1;
        $answer->answerID = 1;
        $this->assertFalse($answer->validate(), "Same answer should not be submitted for the same test instance twice");
    }

    public function testDeleteAnswer()
    {
        $this->assertNotNull(QuizAnswer::findOne(1));
        $answer = new QuizSubmittedAnswer();
        $answer->testinstanceID = 1;
        $answer->answerID = 1;
        $answer->save();
        $this->tester->expectException(\yii\db\IntegrityException::class, function () {
            QuizAnswer::findOne(1)->delete();
        });
    }

    public function testDeleteTestInstance()
    {
        $this->assertNotNull(QuizTestInstance::findOne(1));
        $answer = new QuizSubmittedAnswer();
        $answer->testinstanceID = 1;
        $answer->answerID = 1;
        $answer->save();
        $this->assertFalse(
            QuizTestInstance::findOne(1)->delete()
        );
    }
}
