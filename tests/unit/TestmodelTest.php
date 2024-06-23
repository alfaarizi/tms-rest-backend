<?php

namespace app\tests\unit;

use app\models\ExamTest;
use app\models\ExamQuestionSet;
use app\models\Group;
use app\tests\unit\fixtures\TestFixture;

class TestmodelTest extends \Codeception\Test\Unit
{
    public function _fixtures()
    {
        return [
            'tests' => [
                'class' => TestFixture::class
            ]
        ];
    }

    public function testValidateWithoutParams()
    {
        $test = new ExamTest();
        $this->assertFalse($test->validate(), "Test created without parameters should not be valid.");
    }

    public function testValidateCorrectModel()
    {
        $test = new ExamTest();
        $test->name = 'Test';
        $test->unique = 1;
        $test->shuffled = true;
        $test->duration = 110;
        $test->questionamount = 1;
        $test->availablefrom = date('Y-m-d H:i:s', strtotime('+1 day', time()));
        $test->availableuntil = date('Y-m-d H:i:s', strtotime('+2 day', time()));
        $test->questionsetID = 1;
        $test->groupID = 2000;
        $this->assertTrue($test->validate(), "Test created with correct parameters should be valid.");
    }

    public function testBothDatesAreInvalid()
    {
        $test = ExamTest::findOne(5);
        $this->assertGreaterThan($test->availableuntil, $test->availablefrom);
        $this->assertFalse($test->validate(), "Test with availableFrom property set after availableUntil property should not be valid");
    }

    public function testAvailableUntilDateIsInvalid()
    {
        $test = ExamTest::findOne(2);
        $this->assertGreaterThan($test->availableuntil, date('Y-m-d H:i:s'));
        $this->assertFalse($test->validate(), "Test with availableUntil property set to past date should not be valid");
    }

    public function testNotEnoughQuestions()
    {
        $test = ExamTest::findOne(4);
        $questions = $test->questionSet->getQuestions();
        $this->assertGreaterThan($questions->count(), $test->questionamount);
        $this->assertFalse(
            $test->validate(),
            "Test with higher questionAmount than the number of questions in the question set should not be valid"
        );
    }

    public function testLongDuration()
    {
        $test = ExamTest::findOne(6);
        $this->assertGreaterThan(
            strtotime($test->availableuntil) - strtotime($test->availablefrom),
            $test->duration * 60
        );
        $this->assertFalse($test->validate(), "Duration cannot be longer than availability");
    }

    public function testGetGroup()
    {
        $this->assertNotNull(Group::findOne(2000));
        $test = new ExamTest();
        $test->groupID = 2000;
        $group = $test->getGroup();
        $this->assertNotNull($group, "Related group should be returned");
    }

    public function testGetQuestionSet()
    {
        $this->assertNotNull(ExamQuestionSet::findOne(1));
        $test = new ExamTest();
        $test->questionsetID = 1;
        $questionSet = $test->getQuestionSet();
        $this->assertNotNull($questionSet, "Related question set should be returned");
    }
}
