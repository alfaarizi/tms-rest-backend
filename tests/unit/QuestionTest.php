<?php

namespace app\tests\unit;

use app\models\ExamQuestion;
use app\models\ExamQuestionSet;
use app\models\ExamAnswer;
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
        $question = new ExamQuestion();
        $this->assertFalse($question->validate(), "Question created without parameters should not be valid.");
    }

    public function testValidateCorrectModel()
    {
        $question = new ExamQuestion();
        $question->text = 'Question text';
        $question->questionsetID = 1;
        $this->assertTrue($question->validate(), "Question created with correct parameters should be valid.");
    }

    public function testGetQuestionSet()
    {
        $this->assertNotNull(ExamQuestionSet::findOne(1));
        $question = new ExamQuestion();
        $question->questionsetID = 1;
        $questionSet = $question->getQuestionSet();
        $this->assertNotNull($questionSet, "Related question set should be returned");
    }

    public function testGetAnswers()
    {
        $this->assertNotEmpty(ExamAnswer::find()->where(["questionID" => 1])->all());
        $question = ExamQuestion::findOne(1);
        $answers = $question->getAnswers()->all();
        $this->assertNotEmpty($answers, "Related answers should be returned");
    }

    public function testDeleteCascadesToAnswers()
    {
        $this->assertNotEmpty(ExamAnswer::find()->where(["questionID" => 1])->all());
        $question = ExamQuestion::findOne(1);
        $question->delete();
        $answers = ExamAnswer::find()->where(['questionID' => 1])->all();
        $this->assertEmpty($answers, "Deleting a question causes the related answers to be deleted as well");
    }
}
