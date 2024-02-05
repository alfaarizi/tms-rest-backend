<?php

namespace unit;

use app\models\Notification;
use app\models\NotificationUser;

class NotificationTest extends \Codeception\Test\Unit
{
    use \Codeception\Specify;

    /**
     * @var \UnitTester
     */
    protected $tester;

    /** @specify  */
    private Notification $notification;
    private NotificationUser $notificationUser;

    public function _fixtures()
    {
        return [
            'notifications' => [
                'class' => \app\tests\unit\fixtures\NotificationFixture::class
            ],
            'notificationusers' => [
                'class' => \app\tests\unit\fixtures\NotificationUserFixture::class
            ]
        ];
    }

    // tests

    public function testNotDismissedBy()
    {
        $notifications = Notification::find()->notDismissedBy(1007)->all();
        $this->tester->assertNotEmpty($notifications, "Not dismissed notifications should be fetched");

        $this->tester->assertContains(4001, array_column($notifications, 'id'), "Notification with id 4001 should be fetched");
        $this->tester->assertContains(4003, array_column($notifications, 'id'), "Notification with id 4003 should be fetched");
        $this->tester->assertNotContains(4000, array_column($notifications, 'id'), "Notification with id 4000 should not be fetched");
        $this->tester->assertNotContains(4002, array_column($notifications, 'id'), "Notification with id 4002 should not be fetched");
    }

    public function testFindAvailable()
    {
        $notifications = Notification::find()->findAvailable()->all();
        $this->tester->assertNotEmpty($notifications, "Available notifications should be fetched");

        $this->tester->assertContains(4000, array_column($notifications, 'id'), "Notification with id 4000 should be fetched");
        $this->tester->assertContains(4002, array_column($notifications, 'id'), "Notification with id 4002 should be fetched");
        $this->tester->assertNotContains(4001, array_column($notifications, 'id'), "Notification with id 4001 should not be fetched");
        $this->tester->assertNotContains(4003, array_column($notifications, 'id'), "Notification with id 4003 should not be fetched");
    }

    public function testValidation()
    {
        $this->notification = new Notification();
        $this->notification->message = 'foo';
        $this->notification->startTime = '2020-01-01T00:00:00+01:00';
        $this->notification->endTime = '2020-01-01T00:00:00+01:00';
        $this->notification->dismissable = 1;
        $this->notification->isAvailableForAll = 1;

        $this->specify("All fields should be set in Notification", function () {
            $this->notification->message = null;
            $this->assertFalse($this->notification->validate('message'), "Message should be required");

            $this->notification->endTime = '2019-12-31T23:59:59+01:00';
            $this->assertFalse($this->notification->validate('endTime'), "End time should be after start time");

            $this->notification->startTime = null;
            $this->assertFalse($this->notification->validate('startTime'), "Start time should be required");

            $this->notification->endTime = null;
            $this->assertFalse($this->notification->validate('endTime'), "End time should be required");

            $this->notification->dismissable = null;
            $this->assertFalse($this->notification->validate('dismissable'), "Dismissable should be required");

            $this->notification->isAvailableForAll = null;
            $this->assertFalse($this->notification->validate('isAvailableForAll'), "Is available for all should be required");
        });

        $this->notificationUser = new NotificationUser();
        $this->notificationUser->userID = 1007;
        $this->notificationUser->notificationID = 4000;

        $this->specify("All fields should be set in NotificationUser", function () {
            $this->notificationUser->userID = null;
            $this->assertFalse($this->notificationUser->validate('userID'), "User ID should be required");

            $this->notificationUser->notificationID = null;
            $this->assertFalse($this->notificationUser->validate('notificationID'), "Notification ID should be required");
        });
    }
}
