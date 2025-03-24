<?php

namespace app\tests\unit;

use app\models\QuizTestInstance;
use app\models\QuizTest;
use app\models\User;
use app\tests\unit\fixtures\TestFixture;
use app\tests\unit\fixtures\UserFixture;
use UnitTester;
use yii\base\ErrorException;

class TestInstanceTest extends \Codeception\Test\Unit
{
    protected UnitTester $tester;

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
        $testinstance = new QuizTestInstance();
        $this->assertFalse($testinstance->validate(), "Test instance created without parameters should not be valid.");
    }

    public function testValidateCorrectModel()
    {
        $testinstance = new QuizTestInstance();
        $testinstance->userID = 1000;
        $testinstance->testID = 1;
        $this->assertTrue($testinstance->validate(), "Test instance created with correct parameters should be valid.");
    }

    public function testGetTest()
    {
        $this->assertNotNull(QuizTest::findOne(1));
        $testInstance = new QuizTestInstance();
        $testInstance->testID = 1;
        $test = $testInstance->getTest();
        $this->assertNotNull($test, "Related test should be returned");
    }

    public function testGetUser()
    {
        $this->assertNotNull(User::findOne(1000));
        $testInstance = new QuizTestInstance();
        $testInstance->userID = 1000;
        $user = $testInstance->getUser();
        $this->assertNotNull($user, "Related user should be returned");
    }

    public function testGetVerifiedNotPasswordProtected()
    {
        $this->assertNotNull(QuizTest::findOne(1));

        $testInstance = new QuizTestInstance();
        $testInstance->userID = 1000;
        $testInstance->testID = 1;
        $testInstance->save();

        $this->assertTrue($testInstance->getIsUnlocked());
    }

    public function testDeleteTest()
    {
        $this->assertNotNull(QuizTest::findOne(1));
        $test = new QuizTestInstance();
        $test->testID = 1;
        $test->userID = 1000;
        $test->save();
        $this->tester->expectException(\yii\db\IntegrityException::class, function () {
            QuizTest::findOne(1)->delete();
        });
    }

    public function testDeleteUser()
    {
        $this->assertNotNull(QuizTest::findOne(1));
        $test = new QuizTestInstance();
        $test->testID = 1;
        $test->userID = 1000;
        $test->save();
        $this->tester->expectException(\yii\db\IntegrityException::class, function () {
            User::findOne(1000)->delete();
        });
    }
}
