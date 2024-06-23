<?php

namespace app\tests\unit;

use app\models\ExamAnswer;
use app\models\ExamQuestion;
use app\tests\unit\fixtures\AnswerFixture;

class AnswerTest extends \Codeception\Test\Unit
{
    public function _fixtures()
    {
        return [
            'answers' => [
                'class' => AnswerFixture::class
            ],
        ];
    }

    public function testValidateWithoutParams()
    {
        $answer = new ExamAnswer();
        $this->assertFalse($answer->validate(), "Answer created without parameters should not be valid.");
    }

    public function testValidateCorrectModel()
    {
        $answer = new ExamAnswer();
        $answer->text = 'Answer';
        $answer->correct = true;
        $answer->questionID = 1;
        $this->assertTrue($answer->validate(), "Answer created with correct parameters should be valid.");
    }

    public function testValidateDuplicateText()
    {
        $this->assertNotNull(ExamAnswer::findOne(["text" => "Answer 1"]));
        $answer = new ExamAnswer();
        $answer->text = "Answer 1";
        $answer->questionID = 1;
        $this->assertFalse($answer->validate(), "Two answers cannot have the same text for the same question");
    }

    public function testGetQuestion()
    {
        $this->assertNotNull(ExamQuestion::findOne(1));
        $answer = new ExamAnswer();
        $answer->questionID = 1;
        $question = $answer->getQuestion();
        $this->assertNotNull($question, "Related question should be returned");
    }
}
