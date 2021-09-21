<?php

namespace app\tests\unit;

use app\models\MockAuth;
use app\models\User;
use app\tests\unit\fixtures\UserFixture;

/**
 * Unit tests for the User model.
 */
class UserTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    public function _fixtures()
    {
        return [
            'users' => [
                'class' => UserFixture::class,
            ],
        ];
    }

    /**
     * Test validation rules.
     */
    public function testValidate()
    {
        $user = new User();
        $this->assertFalse($user->validate());

        // Only Neptun code is required.
        $user->neptun = 'BAMTAN';
        $this->assertTrue($user->validate());

        // Email address must be valid if defined.
        $user->email = 'batman';
        $this->assertFalse($user->validate());
        $user->email = 'batman@nanana.hu';
        $this->assertTrue($user->validate());
    }

    /**
     * Test user creation and/or update functionality.
     */
    public function testCreateOrUpdate()
    {
        $now = time();
        $count = User::find()->count();

        // Auth model.
        $authModel = new MockAuth(
            'NEWONE',
            'New One',
            'new.one@elte.hu',
            true,
            false
        );

        // Check for user insertion.
        $user_1 = User::createOrUpdate($authModel);
        $this->assertNotNull($user_1);
        $this->assertGreaterThanOrEqual($now, strtotime($user_1->lastLoginTime));
        $this->assertEquals($count + 1, User::find()->count());

        // Check for user update.
        $user_2 = User::createOrUpdate($authModel);
        $this->assertEquals($user_1->id, $user_2->id);
        $this->assertEquals($count + 1, User::find()->count());
    }
}
