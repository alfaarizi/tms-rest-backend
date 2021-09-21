<?php

namespace app\tests\unit;

use app\models\ExamQuestionSet;
use app\models\ExamQuestion;
use app\models\ExamTest;
use app\models\Course;
use app\tests\unit\fixtures\QuestionFixture;
use app\tests\unit\fixtures\CourseFixture;
use app\tests\unit\fixtures\TestFixture;

class QuestionSetTest extends \Codeception\Test\Unit
{

    public function _fixtures()
    {
        return [
            'questions' => [
                'class' => QuestionFixture::class
            ],
            'courses' => [
                'class' => CourseFixture::class
            ],
            'tests' => [
                'class' => TestFixture::class
            ]
        ];
    }

    public function testValidateWithoutParams()
    {
        $questionSet = new ExamQuestionSet();
        $this->assertFalse($questionSet->validate(), "Question set created without parameters should not be valid.");
    }

    public function testValidateCorrectModel()
    {
        $questionSet = new ExamQuestionSet();
        $questionSet->name = 'Question set';
        $questionSet->courseID = 1;
        $this->assertTrue($questionSet->validate(), "Question set created with correct parameters should be valid.");
    }

    public function testGetQuestions()
    {
        $this->assertNotEmpty(ExamQuestion::find()->where(["questionsetID" => 1])->all());
        $questionSet = ExamQuestionSet::findOne(1);
        $questions = $questionSet->getQuestions()->all();
        $this->assertNotEmpty($questions, "Related questions should be returned");
    }

    public function testGetTests()
    {
        $this->assertNotEmpty(ExamTest::find()->where(["questionsetID" => 1])->all());
        $questionSet = ExamQuestionSet::findOne(1);
        $tests = $questionSet->getTests()->all();
        $this->assertNotEmpty($tests, "Related tests should be returned");
    }

    public function testGetCourse()
    {
        $this->assertNotNull(Course::findOne(1));
        $questionSet = new ExamQuestionSet();
        $questionSet->courseID = 1;
        $course = $questionSet->getCourse();
        $this->assertNotNull($course, "Related course should be returned");
    }

    public function testDeleteCascadesToQuestions()
    {
        $this->assertNotEmpty(ExamQuestion::find()->where(["questionsetID" => 2])->all());
        $questionSet = ExamQuestionSet::findOne(2);
        $questionSet->delete();
        $questions = ExamQuestion::find()->where(['questionsetID' => 2])->all();
        $this->assertEmpty($questions, "Deleting a question set causes the related answers to be deleted as well");
    }

    public function testDeleteCourse()
    {
        $this->assertNotEmpty(ExamQuestionSet::find()->where(["courseID" => 1])->all());
        $this->tester->expectException(\yii\db\IntegrityException::class, function () {
            Course::findOne(1)->delete();
        });
    }
}
