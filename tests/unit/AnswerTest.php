<?php

namespace app\tests\unit;

use app\models\QuizAnswer;
use app\models\QuizQuestion;
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
        $answer = new QuizAnswer();
        $this->assertFalse($answer->validate(), "Answer created without parameters should not be valid.");
    }

    public function testValidateCorrectModel()
    {
        $answer = new QuizAnswer();
        $answer->text = 'Answer';
        $answer->correct = true;
        $answer->questionID = 1;
        $this->assertTrue($answer->validate(), "Answer created with correct parameters should be valid.");
    }

    public function testValidateDuplicateText()
    {
        $this->assertNotNull(QuizAnswer::findOne(["text" => "Answer 1"]));
        $answer = new QuizAnswer();
        $answer->text = "Answer 1";
        $answer->questionID = 1;
        $this->assertFalse($answer->validate(), "Two answers cannot have the same text for the same question");
    }

    public function testGetQuestion()
    {
        $this->assertNotNull(QuizQuestion::findOne(1));
        $answer = new QuizAnswer();
        $answer->questionID = 1;
        $question = $answer->getQuestion();
        $this->assertNotNull($question, "Related question should be returned");
    }
}
