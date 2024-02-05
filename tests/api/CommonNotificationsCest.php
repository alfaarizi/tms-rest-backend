<?php

namespace api;

use ApiTester;
use app\models\Notification;
use app\models\NotificationUser;
use app\tests\unit\fixtures\AccessTokenFixture;
use Codeception\Util\HttpCode;
use app\tests\unit\fixtures\NotificationFixture;
use app\tests\unit\fixtures\NotificationUserFixture;

class CommonNotificationsCest
{
    public const NOTIFICATION_SCHEMA = [
        'id' => 'integer',
        'message' => 'string',
        'dismissable' => 'boolean',
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

    public function publicIndex(ApiTester $I)
    {
        $I->sendGet('/common/notifications');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseMatchesJsonType(self::NOTIFICATION_SCHEMA, '$.[*]');
        $I->seeResponseContainsJson(
            [
                [
                    'id' => 4000,
                    'message' => 'Test message 1.',
                    'dismissable' => true,
                ],
            ]
        );
        $I->cantSeeResponseContainsJson(['id' => 4001]);
        $I->cantSeeResponseContainsJson(['id' => 4002]);
        $I->cantSeeResponseContainsJson(['id' => 4003]);
    }

    public function privateIndex(ApiTester $I)
    {
        $I->amBearerAuthenticated("BATMAN;12345");
        $I->sendGet('/common/notifications');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseMatchesJsonType(self::NOTIFICATION_SCHEMA, '$.[*]');
        $I->seeResponseContainsJson(
            [
                [
                    'id' => 4000,
                    'message' => 'Test message 1.',
                    'dismissable' => true,
                ],
                [
                    'id' => 4002,
                    'message' => 'Test message 3.',
                    'dismissable' => true,
                ]
            ]
        );
        $I->cantSeeResponseContainsJson(['id' => 4001]);
        $I->cantSeeResponseContainsJson(['id' => 4003]);
    }

    public function dismissUnauthorized(ApiTester $I)
    {
        $I->sendPost('/common/notifications/dismiss?notificationID=4000');
        $I->seeResponseCodeIs(HttpCode::UNAUTHORIZED);
    }

    public function dismissNoQueryParam(ApiTester $I)
    {
        $I->amBearerAuthenticated("BATMAN;12345");
        $I->sendPost('/common/notifications/dismiss');
        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);
    }

    public function dismissNotFoundNotification(ApiTester $I)
    {
        $I->amBearerAuthenticated("BATMAN;12345");
        $I->sendPost('/common/notifications/dismiss?notificationID=0');
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function dismissNotDismissableNotification(ApiTester $I)
    {
        $I->amBearerAuthenticated("BATMAN;12345");
        $I->sendPost('/common/notifications/dismiss?notificationID=4001');
        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);
        $I->cantSeeRecord(
            NotificationUser::class,
            [
                'userID' => 1000,
                'notificationID' => 4001,
            ]
        );
    }

    public function dismissValid(ApiTester $I)
    {
        $I->amBearerAuthenticated("BATMAN;12345");
        $I->sendPost('/common/notifications/dismiss?notificationID=4000');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseMatchesJsonType(self::NOTIFICATION_SCHEMA);
        $I->seeResponseContainsJson(
            [
                'message' => 'Test message 1.',
                'dismissable' => true,
            ]
        );
        $I->seeRecord(
            NotificationUser::class,
            [
                'userID' => 1000,
                'notificationID' => 4000,
            ]
        );
    }
}
