<?php

namespace app\tests\api;

use ApiTester;
use app\models\Notification;
use app\models\NotificationUser;
use app\tests\DateFormat;
use app\tests\unit\fixtures\AccessTokenFixture;
use app\tests\unit\fixtures\GroupFixture;
use app\tests\unit\fixtures\InstructorCourseFixture;
use app\tests\unit\fixtures\InstructorGroupFixture;
use app\tests\unit\fixtures\SubscriptionFixture;
use Codeception\Util\HttpCode;
use app\tests\unit\fixtures\NotificationFixture;
use app\tests\unit\fixtures\NotificationUserFixture;
use Yii;

class InstructorNotificationsCest
{
    public const NOTIFICATION_SCHEMA = [
        'id' => 'integer',
        'message' => 'string',
        'startTime' => 'string',
        'endTime' => 'string',
        'dismissible' => 'boolean',
        'groupID' => 'integer',
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
            'groups' => [
                'class' => GroupFixture::class
            ],
            'instructorcourses' => [
                'class' => InstructorCourseFixture::class,
            ],
            'instructorctorgroups' => [
                'class' => InstructorGroupFixture::class,
            ],
            'subscriptions' => [
                'class' => SubscriptionFixture::class
            ],
        ];
    }

    public function _before(ApiTester $I)
    {
        $I->amBearerAuthenticated("TEACH2;VALID");
        Yii::$app->language = 'en-US';
    }

    public function index(ApiTester $I)
    {
        $I->sendGet('/instructor/notifications?groupID=2000');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseMatchesJsonType(self::NOTIFICATION_SCHEMA, '$.[*]');
        $I->seeResponseContainsJson(['id' => 4006]);
    }

    public function indexNotManagedGroup(ApiTester $I)
    {
        $I->sendGet('/instructor/notifications?groupID=2007');
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function view(ApiTester $I)
    {
        $I->sendGet('/instructor/notifications/4006');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseMatchesJsonType(self::NOTIFICATION_SCHEMA);
        $I->seeResponseContainsJson(
            [
                'id' => 4006,
                'message' => 'Test message 7.',
                'startTime' => '2020-01-01T00:00:00+01:00',
                'endTime' => '3023-01-02T00:00:00+01:00',
                'dismissible' => false,
                'groupID' => 2000,
            ]
        );
    }

    public function viewNotFound(ApiTester $I)
    {
        $I->sendGet('/instructor/notifications/0');
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }


    public function viewNotGroupLevelNotification(ApiTester $I)
    {
        $I->sendGet('/instructor/notifications/4000');
        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);
    }

    public function createValid(ApiTester $I)
    {
        $startTime = new \DateTime('+1 day');
        $endTime = new \DateTime('+2 day');
        $I->sendPost(
            '/instructor/notifications',
            [
                'message' => 'Created',
                'startTime' => $startTime->format(\DateTime::ATOM),
                'endTime' => $endTime->format(\DateTime::ATOM),
                'dismissible' => true,
                'groupID' => 2000,
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
            ]
        );
        $I->seeRecord(
            Notification::class,
            [
                'message' => 'Created',
                'startTime' => $startTime->format(DateFormat::MYSQL),
                'endTime' => $endTime->format(DateFormat::MYSQL),
                'dismissible' => true,
                'groupID' => 2000,
            ]
        );
    }

    public function createEmptyMessage(ApiTester $I)
    {
        $startTime = date(\DateTime::ATOM, strtotime('+1 day'));
        $endTime = date(\DateTime::ATOM, strtotime('+2 day'));
        $I->sendPost(
            '/instructor/notifications',
            [
                'message' => '',
                'startTime' => $startTime,
                'endTime' => $endTime,
                'dismissible' => true,
                'groupID' => 2000,
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
            ]
        );
    }

    public function createInvalidDate(ApiTester $I)
    {
        // check end time is before start time
        $startTime = date(\DateTime::ATOM, strtotime('+1 day'));
        $endTime = date(\DateTime::ATOM, strtotime('-2 day'));
        $I->sendPost(
            '/instructor/notifications',
            [
                'message' => 'Teszt',
                'startTime' => $startTime,
                'endTime' => $endTime,
                'dismissible' => true,
                'groupID' => 2000,
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
            ]
        );
    }

    public function updateNotFound(ApiTester $I)
    {
        $I->sendPost('/instructor/notifications/0');
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function updateEmptyMessage(ApiTester $I)
    {
        $I->sendPatch(
            '/instructor/notifications/4006',
            [
                'id' => 4006,
                'message' => '',
            ]
        );
        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->seeRecord(
            Notification::class,
            [
                'id' => 4006,
                'message' => 'Test message 7.',
            ]
        );
    }


    public function updateValid(ApiTester $I)
    {
        $I->sendPatch(
            '/instructor/notifications/4006',
            [
                'message' => 'Updated.',
                'dismissible' => false,
            ]
        );
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseContainsJson(
            [
                'id' => 4006,
                'message' => 'Updated.',
                'dismissible' => false,
            ]
        );
        $I->seeRecord(
            Notification::class,
            [
                'id' => 4006,
                'message' => 'Updated.',
                'dismissible' => false,
                'groupID' => 2000,
            ]
        );
    }

    public function deleteNotFound(ApiTester $I)
    {
        $I->sendDelete('/notifications/0');
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }


    public function deleteNotGroupLevelNotification(ApiTester $I)
    {
        $I->sendDelete('/instructor/notifications/4000');
        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);
    }

    public function delete(ApiTester $I)
    {
        $I->sendDelete('instructor/notifications/4006');
        $I->seeResponseCodeIs(HttpCode::NO_CONTENT);
        $I->cantSeeRecord(Notification::class, ['id' => 4006]);

        // Delete notification users
        $I->cantSeeRecord(NotificationUser::class, ['notificationID' => 4006]);
    }
}
