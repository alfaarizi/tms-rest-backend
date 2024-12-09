<?php

namespace app\tests\api;

use ApiTester;
use app\models\Notification;
use app\models\NotificationUser;
use app\tests\DateFormat;
use app\tests\unit\fixtures\AccessTokenFixture;
use Codeception\Util\HttpCode;
use app\tests\unit\fixtures\NotificationFixture;
use app\tests\unit\fixtures\NotificationUserFixture;

class AdminNotificationsCest
{
    public const NOTIFICATION_SCHEMA = [
        'id' => 'integer',
        'message' => 'string',
        'startTime' => 'string',
        'endTime' => 'string',
        'scope' => 'string',
        'dismissible' => 'boolean',
    ];


    public function _fixtures()
    {
        return [
            'accesstokens' => [
                'class' => AccessTokenFixture::class,
            ],
            'notifications' => [
                'class' => NotificationFixture::class,
            ],
            'notificationusers' => [
                'class' => NotificationUserFixture::class,
            ],
        ];
    }

    public function _before(ApiTester $I)
    {
        $I->amBearerAuthenticated("BATMAN;12345");
    }

    public function index(ApiTester $I)
    {
        $I->sendGet('/admin/notifications');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseMatchesJsonType(self::NOTIFICATION_SCHEMA, '$.[*]');
        $I->seeResponseContainsJson(
            [
                [
                    'id' => 4000,
                    'message' => 'Test message 1.',
                    'startTime' => '2020-01-01T00:00:00+01:00',
                    'endTime' => '3023-12-05T00:00:00+01:00',
                    'dismissible' => true,
                    'scope' => Notification::SCOPE_EVERYONE,
                ],
                [
                    'id' => 4001,
                    'message' => 'Test message 2.',
                    'startTime' => '2020-01-01T00:00:00+01:00',
                    'endTime' => '2020-01-02T00:00:00+01:00',
                    'dismissible' => false,
                    'scope' => Notification::SCOPE_EVERYONE,
                ],
                [
                    'id' => 4002,
                    'message' => 'Test message 3.',
                    'startTime' => '2020-01-01T00:00:00+01:00',
                    'endTime' => '3023-12-05T00:00:00+01:00',
                    'dismissible' => true,
                    'scope' => Notification::SCOPE_USER,
                ],
                [
                    'id' => 4003,
                    'message' => 'Test message 4.',
                    'startTime' => '3020-01-01T00:00:00+01:00',
                    'endTime' => '3020-01-02T00:00:00+01:00',
                    'dismissible' => false,
                    'scope' => Notification::SCOPE_USER,
                ],
            ]
        );
    }

    public function view(ApiTester $I)
    {
        $I->sendGet('/admin/notifications/4000');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseMatchesJsonType(self::NOTIFICATION_SCHEMA);
        $I->seeResponseContainsJson(
            [
                'id' => 4000,
                'message' => 'Test message 1.',
                'startTime' => '2020-01-01T00:00:00+01:00',
                'endTime' => '3023-12-05T00:00:00+01:00',
                'dismissible' => true,
                'scope' => Notification::SCOPE_EVERYONE,
            ]
        );
    }

    public function viewNotFound(ApiTester $I)
    {
        $I->sendGet('/admin/notifications/0');
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function createValid(ApiTester $I)
    {
        $startTime = new \DateTime('+1 day');
        $endTime = new \DateTime('+2 day');
        $I->sendPost(
            '/admin/notifications',
            [
                'message' => 'Created',
                'startTime' => $startTime->format(\DateTime::ATOM),
                'endTime' => $endTime->format(\DateTime::ATOM),
                'dismissible' => true,
                'scope' => Notification::SCOPE_EVERYONE,
            ]
        );
        $I->seeResponseCodeIs(HttpCode::CREATED);
        $I->seeResponseMatchesJsonType(self::NOTIFICATION_SCHEMA);
        $I->seeResponseContainsJson(
            [
                'message' => 'Created',
                'startTime' => $startTime->format(\DateTime::ATOM),
                'endTime' => $endTime->format(\DateTime::ATOM),
                'dismissible' => true,
                'scope' => Notification::SCOPE_EVERYONE,
            ]
        );
        $I->seeRecord(
            Notification::class,
            [
                'message' => 'Created',
                'startTime' => $startTime->format(DateFormat::MYSQL),
                'endTime' => $endTime->format(DateFormat::MYSQL),
                'dismissible' => true,
                'scope' => Notification::SCOPE_EVERYONE,
            ]
        );
    }

    public function createEmptyMessage(ApiTester $I)
    {
        $startTime = date(\DateTime::ATOM, strtotime('+1 day'));
        $endTime = date(\DateTime::ATOM, strtotime('+2 day'));
        $I->sendPost(
            '/admin/notifications',
            [
                'message' => '',
                'startTime' => $startTime,
                'endTime' => $endTime,
                'dismissible' => true,
                'scope' => Notification::SCOPE_EVERYONE,
            ]
        );
        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->cantSeeRecord(
            Notification::class,
            [
                'message' => '',
                'startTime' => $startTime,
                'endTime' => $endTime,
                'dismissible' => true,
                'scope' => Notification::SCOPE_EVERYONE,
            ]
        );
    }

    public function createInvalidDate(ApiTester $I)
    {
        // check end time is before start time
        $startTime = date(\DateTime::ATOM, strtotime('+1 day'));
        $endTime = date(\DateTime::ATOM, strtotime('-2 day'));
        $I->sendPost(
            '/admin/notifications',
            [
                'message' => 'Teszt',
                'startTime' => $startTime,
                'endTime' => $endTime,
                'dismissible' => true,
                'scope' => Notification::SCOPE_EVERYONE,
            ]
        );
        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->cantSeeRecord(
            Notification::class,
            [
                'message' => 'Teszt',
                'startTime' => $startTime,
                'endTime' => $endTime,
                'dismissible' => true,
                'scope' => Notification::SCOPE_EVERYONE,
            ]
        );
    }

    public function updateNotFound(ApiTester $I)
    {
        $I->sendPost('/admin/notifications/0');
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function updateEmptyMessage(ApiTester $I)
    {
        $I->sendPatch(
            '/admin/notifications/4000',
            [
                'id' => 4000,
                'message' => '',
            ]
        );
        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->seeRecord(
            Notification::class,
            [
                'id' => 4000,
                'message' => 'Test message 1.',
            ]
        );
    }

    public function updateInvalidDate(ApiTester $I)
    {
        $startTime = date(\DateTime::ATOM, strtotime('+1 day'));
        $endTime = date(\DateTime::ATOM, strtotime('-2 day'));

        $I->sendPatch(
            '/admin/notifications/4000',
            [
                'id' => 4000,
                'message' => 'Update 2',
                'startTime' => $startTime,
                'endTime' => $endTime,
            ]
        );
        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->seeRecord(
            Notification::class,
            [
                'id' => 4000,
                'message' => 'Test message 1.',
            ]
        );
    }

    public function updateValid(ApiTester $I)
    {
        $I->sendPatch(
            '/admin/notifications/4000',
            [
                'message' => 'Updated.',
                'dismissible' => false,
                'scope' => Notification::SCOPE_USER,
            ]
        );
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseContainsJson(
            [
                'id' => 4000,
                'message' => 'Updated.',
                'startTime' => '2020-01-01T00:00:00+01:00',
                'endTime' => '3023-12-05T00:00:00+01:00',
                'dismissible' => false,
                'scope' => Notification::SCOPE_USER,
            ]
        );
        $I->seeRecord(
            Notification::class,
            [
                'id' => 4000,
                'message' => 'Updated.',
                'dismissible' => false,
                'scope' => Notification::SCOPE_USER,
            ]
        );
    }

    public function deleteNotFound(ApiTester $I)
    {
        $I->sendDelete('/notifications/0');
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function delete(ApiTester $I)
    {
        $I->sendDelete('admin/notifications/4000');
        $I->seeResponseCodeIs(HttpCode::NO_CONTENT);
        $I->cantSeeRecord(Notification::class, ['id' => 4000]);

        // Delete notificationusers
        $I->cantSeeRecord(NotificationUser::class, ['notificationID' => 4000]);
    }
}
