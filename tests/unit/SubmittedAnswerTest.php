<?php

namespace app\tests\unit;

use app\models\ExamSubmittedAnswer;
use app\models\ExamTestInstance;
use app\models\ExamAnswer;
use app\tests\unit\fixtures\SubmittedAnswerFixture;
use app\tests\unit\fixtures\TestInstanceQuestionFixture;

class SubmittedAnswerTest extends \Codeception\Test\Unit
{

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
        $submittedAnswer = new ExamSubmittedAnswer();
        $this->assertFalse($submittedAnswer->validate(), "Submitted answer created without parameters should not be valid.");
    }

    public function testValidateCorrectModel()
    {
        $submittedAnswer = new ExamSubmittedAnswer();
        $submittedAnswer->testinstanceID = 2;
        $submittedAnswer->answerID = 1;
        $this->assertTrue($submittedAnswer->validate(), "Submitted answer created with parameters should be valid.");
    }

    public function testValidateWithoutAnswerId()
    {
        $submittedAnswer = new ExamSubmittedAnswer();
        $submittedAnswer->testinstanceID = 1;
        $this->assertTrue($submittedAnswer->validate(), "Submitted answer created with parameters should be valid.");
    }

    public function testGetters()
    {
        $submittedAnswer = new ExamSubmittedAnswer();
        $submittedAnswer->testinstanceID = 1;
        $submittedAnswer->answerID = 1;
        $this->assertNotNull($submittedAnswer->getAnswer());
        $this->assertNotNull($submittedAnswer->getTestInstance());
    }

    public function testSubmitAnswerTwice()
    {
        $this->assertNotNull(ExamSubmittedAnswer::find()->where(["testinstanceID" => 1])->andWhere(["answerID" => 1])->one());
        $answer = new ExamSubmittedAnswer();
        $answer->testinstanceID = 1;
        $answer->answerID = 1;
        $this->assertFalse($answer->validate(), "Same answer should not be submitted for the same test instance twice");
    }

    public function testDeleteAnswer()
    {
        $this->assertNotNull(ExamAnswer::findOne(1));
        $answer = new ExamSubmittedAnswer();
        $answer->testinstanceID = 1;
        $answer->answerID = 1;
        $answer->save();
        $this->tester->expectException(\yii\db\IntegrityException::class, function () {
            ExamAnswer::findOne(1)->delete();
        });
    }

    public function testDeleteTestInstance()
    {
        $this->assertNotNull(ExamTestInstance::findOne(1));
        $answer = new ExamSubmittedAnswer();
        $answer->testinstanceID = 1;
        $answer->answerID = 1;
        $answer->save();
        $this->tester->expectException(\yii\db\IntegrityException::class, function () {
            ExamTestInstance::findOne(1)->delete();
        });
    }
}
