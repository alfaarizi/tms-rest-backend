<?php

namespace app\tests\unit;

use app\models\MockAuth;
use app\models\User;
use app\tests\unit\fixtures\UserFixture;
use yii\web\BadRequestHttpException;

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

        // Only user code is required.
        $user->userCode = 'BAMTAN';
        $this->assertTrue($user->validate());

        // Email address must be valid if defined.
        $user->email = 'batman';
        $this->assertFalse($user->validate());
        $user->email = 'batman@nanana.hu';
        $this->assertTrue($user->validate());
    }

    /**
     * Test validation rules in SCENARIO_SETTINGS.
     */
    public function testSettingsValidate()
    {
        $user = new User();
        $user->scenario = User::SCENARIO_SETTINGS;
        $this->assertFalse($user->validate());

        // User code is still not enough.
        $user->userCode = 'BATMAN';
        $this->assertFalse($user->validate());

        // Defining locale and notifications target is enough.
        $user->email = 'batman@nanana.hu';
        $user->locale = 'en-US';
        $user->notificationTarget = 'official';
        $this->assertTrue($user->validate());

        // ...but only if they are valid.
        $user->locale = 'de-AT';
        $this->assertFalse($user->validate());
        $user->locale = 'en-US';
        $user->notificationTarget = 'foo';
        $this->assertFalse($user->validate());
        $user->notificationTarget = 'official';

        // However, if we set the custom email address, it should be valid.
        $user->customEmail = 'batman';
        $this->assertFalse($user->validate());
        $user->customEmail = 'batman@nanana.com';
        $this->assertTrue($user->validate());
        $user->beforeSave(/* $insert = */ false);
        $this->assertFalse($user->customEmailConfirmed);
    }

    /**
     * Test custom email confirmation.
     */
    public function testConfirmationCode()
    {
        $tests = [
            [ 'oldEmail' => null, 'email' => null, 'confirmation' => false ],
            [ 'oldEmail' => null, 'email' => '', 'confirmation' => false ],
            [ 'oldEmail' => null, 'email' => 'poseidon@olympos.gr', 'confirmation' => true ],
            [ 'oldEmail' => 'batman@nanana.hu', 'email' => null, 'confirmation' => false ],
            [ 'oldEmail' => 'batman@nanana.hu', 'email' => '', 'confirmation' => false ],
            [ 'oldEmail' => 'batman@nanana.hu', 'email' => 'poseidon@olympos.gr', 'confirmation' => true ],
            [ 'oldEmail' => 'poseidon@olympos.gr', 'email' => null, 'confirmation' => false ],
            [ 'oldEmail' => 'poseidon@olympos.gr', 'email' => '', 'confirmation' => false ],
            [ 'oldEmail' => 'poseidon@olympos.gr', 'email' => 'poseidon@olympos.gr', 'confirmation' => false ],
        ];
        foreach ($tests as $case) {
            $prevUser = User::findOne(['userCode' => 'POSEID']);
            if ($prevUser) {
                $prevUser->delete();
            }
            $user = new User();
            $user->userCode = 'POSEID';
            $user->customEmail = $case['oldEmail'];
            $user->save();
            $this->assertNull($user->getConfirmationCodeIfNecessary());
            $user->customEmail = $case['email'];
            $confirmationCode = $user->getConfirmationCodeIfNecessary();
            $this->assertEquals($confirmationCode, $user->customEmailConfirmationCode);
            if ($case['confirmation']) {
                $this->assertNotEmpty($confirmationCode);
                // Not yet persisted
                $this->assertNull(User::findByConfirmationCode($confirmationCode));
                $user->save();
                $user2 = User::findByConfirmationCode($confirmationCode);
                /** @phpstan-ignore-next-line method.impossibleType ($user2 can be null here) */
                $this->assertNotNull($user2);
                $this->assertEquals($user->id, $user2->id);
                /** @phpstan-ignore-next-line method.impossibleType ($confirmationCode can be null here) */
                $this->assertNotNull($confirmationCode);
                $user->markAttributeDirty('customEmail');
            } else {
                $this->assertNull($confirmationCode);
            }
        }
    }

    public function testNotificationEmail()
    {
        $officialAddress = 'batman@inf.elte.hu';
        $customAddress = 'batman@nanana.hu';
        $user = new User();
        $user->userCode = 'BATMAN';
        // There’s no data yet. This is perfectly valid; it should
        // return null instead of throwing some exception.
        $this->assertNull($user->notificationEmail);
        $user->email = $officialAddress;
        $this->assertEquals($officialAddress, $user->notificationEmail);
        $user->notificationTarget = 'custom';
        // The user wants notifications sent to the custom address,
        // but it’s not set. We should fall back to the official one.
        $this->assertEquals($officialAddress, $user->notificationEmail);
        $user->customEmail = $customAddress;
        // The custom address is set, but not confirmed yet. We should
        // still fall back to the official one.
        $this->assertEquals($officialAddress, $user->notificationEmail);
        $user->customEmailConfirmed = true;
        // Finally everything is set. Now time to send mails to the
        // custom address!
        $this->assertEquals($customAddress, $user->notificationEmail);
        // Wait, I don’t want to get mails anywhere. Can we send them
        // to /dev/null?
        $user->notificationTarget = 'none';
        $this->assertNull($user->notificationEmail);
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

    /**
     * @throws BadRequestHttpException
     */
    public function testSearchNoResult()
    {
        $result = User::search("bla");
        $this->assertEmpty($result);
    }

    public function testSearchTooShortText()
    {
        $this->expectException(BadRequestHttpException::class);
        User::search("s");
    }

    /**
     * @throws BadRequestHttpException
     */
    public function testSearchResultPrefixName()
    {
        $result = User::search("student");
        $this->assertEquals(6, count($result));
    }

    /**
     * @throws BadRequestHttpException
     */
    public function testSearchResultPostfixName()
    {
        $result = User::search("ThrEe");
        $this->assertEquals(2, count($result));
    }

    /**
     * @throws BadRequestHttpException
     */
    public function testSearchResultPrefixUserCode()
    {
        $result = User::search("batma");
        $this->assertEquals(1, count($result));
    }

    /**
     * @throws BadRequestHttpException
     */
    public function testSearchResultMiddleUserCode()
    {
        $result = User::search("atma");
        $this->assertEquals(1, count($result));
    }

    /**
     * @throws BadRequestHttpException
     */
    public function testSearchResultPostfixUserode()
    {
        $result = User::search("tman");
        $this->assertEquals(1, count($result));
    }
}
