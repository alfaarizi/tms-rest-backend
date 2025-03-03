<?php

namespace app\tests\unit;

use app\models\QuizQuestionSet;
use app\models\QuizQuestion;
use app\models\QuizTest;
use app\models\Course;
use app\tests\unit\fixtures\QuestionFixture;
use app\tests\unit\fixtures\CourseFixture;
use app\tests\unit\fixtures\TestFixture;
use UnitTester;

class QuestionSetTest extends \Codeception\Test\Unit
{
    protected UnitTester $tester;

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
        $questionSet = new QuizQuestionSet();
        $this->assertFalse($questionSet->validate(), "Question set created without parameters should not be valid.");
    }

    public function testValidateCorrectModel()
    {
        $questionSet = new QuizQuestionSet();
        $questionSet->name = 'Question set';
        $questionSet->courseID = 4000;
        $this->assertTrue($questionSet->validate(), "Question set created with correct parameters should be valid.");
    }

    public function testGetQuestions()
    {
        $this->assertNotEmpty(QuizQuestion::find()->where(["questionsetID" => 1])->all());
        $questionSet = QuizQuestionSet::findOne(1);
        $questions = $questionSet->getQuestions()->all();
        $this->assertNotEmpty($questions, "Related questions should be returned");
    }

    public function testGetTests()
    {
        $this->assertNotEmpty(QuizTest::find()->where(["questionsetID" => 1])->all());
        $questionSet = QuizQuestionSet::findOne(1);
        $tests = $questionSet->getTests()->all();
        $this->assertNotEmpty($tests, "Related tests should be returned");
    }

    public function testGetCourse()
    {
        $this->assertNotNull(Course::findOne(4000));
        $questionSet = new QuizQuestionSet();
        $questionSet->courseID = 4000;
        $course = $questionSet->getCourse();
        $this->assertNotNull($course, "Related course should be returned");
    }

    public function testDeleteCascadesToQuestions()
    {
        $this->assertNotEmpty(QuizQuestion::find()->where(["questionsetID" => 2])->all());
        $questionSet = QuizQuestionSet::findOne(2);
        $questionSet->delete();
        $questions = QuizQuestion::find()->where(['questionsetID' => 2])->all();
        $this->assertEmpty($questions, "Deleting a question set causes the related answers to be deleted as well");
    }

    public function testDeleteCourse()
    {
        $this->assertNotEmpty(QuizQuestionSet::find()->where(["courseID" => 4000])->all());
        $this->tester->expectException(\yii\db\IntegrityException::class, function () {
            Course::findOne(4000)->delete();
        });
    }
}
