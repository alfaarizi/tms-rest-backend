<?php

namespace app\tests\unit;

use app\models\QuizQuestion;
use app\models\QuizQuestionSet;
use app\models\QuizAnswer;
use app\tests\unit\fixtures\AnswerFixture;
use app\tests\unit\fixtures\TestInstanceQuestionFixture;

class QuestionTest extends \Codeception\Test\Unit
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
        $question = new QuizQuestion();
        $this->assertFalse($question->validate(), "Question created without parameters should not be valid.");
    }

    public function testValidateCorrectModel()
    {
        $question = new QuizQuestion();
        $question->text = 'Question text';
        $question->questionsetID = 1;
        $this->assertTrue($question->validate(), "Question created with correct parameters should be valid.");
    }

    public function testGetQuestionSet()
    {
        $this->assertNotNull(QuizQuestionSet::findOne(1));
        $question = new QuizQuestion();
        $question->questionsetID = 1;
        $questionSet = $question->getQuestionSet();
        $this->assertNotNull($questionSet, "Related question set should be returned");
    }

    public function testGetAnswers()
    {
        $this->assertNotEmpty(QuizAnswer::find()->where(["questionID" => 1])->all());
        $question = QuizQuestion::findOne(1);
        $answers = $question->getAnswers()->all();
        $this->assertNotEmpty($answers, "Related answers should be returned");
    }

    public function testDeleteCascadesToAnswers()
    {
        $this->assertNotEmpty(QuizAnswer::find()->where(["questionID" => 1])->all());
        $question = QuizQuestion::findOne(1);
        $question->delete();
        $answers = QuizAnswer::find()->where(['questionID' => 1])->all();
        $this->assertEmpty($answers, "Deleting a question causes the related answers to be deleted as well");
    }
}
