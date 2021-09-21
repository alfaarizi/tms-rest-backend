<?php

namespace app\tests\unit;

use app\models\ExamTestInstance;
use app\models\ExamTest;
use app\models\User;
use app\tests\unit\fixtures\TestFixture;
use app\tests\unit\fixtures\UserFixture;

class TestInstanceTest extends \Codeception\Test\Unit
{

    public function _fixtures()
    {
        return [
            'users' => [
                'class' => UserFixture::class
            ],
            'tests' => [
                'class' => TestFixture::class
            ]
        ];
    }

    public function testValidateWithoutParams()
    {
        $testinstance = new ExamTestInstance();
        $this->assertFalse($testinstance->validate(), "Test instance created without parameters should not be valid.");
    }

    public function testValidateCorrectModel()
    {
        $testinstance = new ExamTestInstance();
        $testinstance->userID = 1;
        $testinstance->testID = 1;
        $this->assertTrue($testinstance->validate(), "Test instance created with correct parameters should be valid.");
    }

    public function testGetTest()
    {
        $this->assertNotNull(ExamTest::findOne(1));
        $testInstance = new ExamTestInstance();
        $testInstance->testID = 1;
        $test = $testInstance->getTest();
        $this->assertNotNull($test, "Related test should be returned");
    }

    public function testGetUser()
    {
        $this->assertNotNull(User::findOne(1));
        $testInstance = new ExamTestInstance();
        $testInstance->userID = 1;
        $user = $testInstance->getUser();
        $this->assertNotNull($user, "Related user should be returned");
    }

    public function testDeleteTest()
    {
        $this->assertNotNull(ExamTest::findOne(1));
        $test = new ExamTestInstance();
        $test->testID = 1;
        $test->userID = 1;
        $test->save();
        $this->tester->expectException(\yii\db\IntegrityException::class, function () {
            ExamTest::findOne(1)->delete();
        });
    }

    public function testDeleteUser()
    {
        $this->assertNotNull(ExamTest::findOne(1));
        $test = new ExamTestInstance();
        $test->testID = 1;
        $test->userID = 1;
        $test->save();
        $this->tester->expectException(\yii\db\IntegrityException::class, function () {
            User::findOne(1)->delete();
        });
    }
}
