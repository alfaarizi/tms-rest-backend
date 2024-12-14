<?php

namespace app\tests\api;

use ApiTester;
use app\models\NotificationUser;
use app\tests\unit\fixtures\AccessTokenFixture;
use app\tests\unit\fixtures\InstructorGroupFixture;
use app\tests\unit\fixtures\SubscriptionFixture;
use Codeception\Util\HttpCode;
use app\tests\unit\fixtures\NotificationFixture;
use app\tests\unit\fixtures\NotificationUserFixture;

class CommonNotificationsCest
{
    public const NOTIFICATION_SCHEMA = [
        'id' => 'integer',
        'message' => 'string',
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
            'subscriptions' => [
                'class' => SubscriptionFixture::class,
            ],
            'instructorctorgroups' => [
                'class' => InstructorGroupFixture::class,
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
                    'dismissible' => true,
                ],
            ]
        );
        $I->cantSeeResponseContainsJson(['id' => 4001]);
        $I->cantSeeResponseContainsJson(['id' => 4002]);
        $I->cantSeeResponseContainsJson(['id' => 4003]);
        $I->cantSeeResponseContainsJson(['id' => 4004]);
        $I->cantSeeResponseContainsJson(['id' => 4005]);
    }

    public function privateIndexAdmin(ApiTester $I)
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
                    'dismissible' => true,
                ],
                [
                    'id' => 4002,
                    'message' => 'Test message 3.',
                    'dismissible' => true,
                ]
            ]
        );
        $I->cantSeeResponseContainsJson(['id' => 4001]);
        $I->cantSeeResponseContainsJson(['id' => 4003]);
        $I->cantSeeResponseContainsJson(['id' => 4004]);
        $I->cantSeeResponseContainsJson(['id' => 4005]);
        $I->cantSeeResponseContainsJson(['id' => 4006]);
        $I->cantSeeResponseContainsJson(['id' => 4007]);
    }

    public function privateIndexStudentWithGroups(ApiTester $I)
    {
        $I->amBearerAuthenticated("STUD02;VALID");
        $I->sendGet('/common/notifications');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseMatchesJsonType(self::NOTIFICATION_SCHEMA, '$.[*]');
        $I->seeResponseContainsJson(
            [
                [
                    'id' => 4000,
                    'message' => 'Test message 1.',
                    'dismissible' => true,
                ],
                [
                    'id' => 4002,
                    'message' => 'Test message 3.',
                    'dismissible' => true,
                ],
                [
                    'id' => 4004,
                    'message' => 'Test message 5.',
                    'dismissible' => true,
                ],
                [
                    'id' => 4006,
                    'message' => 'Test message 7.',
                    'dismissible' => false,
                ],
                [
                    'id' => 4007,
                    'message' => 'Test message 8.',
                    'dismissible' => true,
                ],
            ]
        );
        $I->cantSeeResponseContainsJson(['id' => 4001]);
        $I->cantSeeResponseContainsJson(['id' => 4003]);
        $I->cantSeeResponseContainsJson(['id' => 4005]);
    }

    public function privateIndexStudentWithNotAllGroups(ApiTester $I)
    {
        $I->amBearerAuthenticated("STUD01;VALID");
        $I->sendGet('/common/notifications');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseMatchesJsonType(self::NOTIFICATION_SCHEMA, '$.[*]');
        $I->seeResponseContainsJson(
            [
                [
                    'id' => 4000,
                    'message' => 'Test message 1.',
                    'dismissible' => true,
                ],
                [
                    'id' => 4002,
                    'message' => 'Test message 3.',
                    'dismissible' => true,
                ],
                [
                    'id' => 4004,
                    'message' => 'Test message 5.',
                    'dismissible' => true,
                ],
                [
                    'id' => 4006,
                    'message' => 'Test message 7.',
                    'dismissible' => false,
                ],
            ]
        );
        $I->cantSeeResponseContainsJson(['id' => 4001]);
        $I->cantSeeResponseContainsJson(['id' => 4003]);
        $I->cantSeeResponseContainsJson(['id' => 4005]);
        $I->cantSeeResponseContainsJson(['id' => 4007]);
    }

    public function privateIndexInstructorWithoutGroups(ApiTester $I)
    {
        $I->amBearerAuthenticated("TEACH5;VALID");
        $I->sendGet('/common/notifications');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseMatchesJsonType(self::NOTIFICATION_SCHEMA, '$.[*]');
        $I->seeResponseContainsJson(
            [
                [
                    'id' => 4000,
                    'message' => 'Test message 1.',
                    'dismissible' => true,
                ],
                [
                    'id' => 4002,
                    'message' => 'Test message 3.',
                    'dismissible' => true,
                ],
                [
                    'id' => 4005,
                    'message' => 'Test message 6.',
                    'dismissible' => true,
                ],
            ]
        );
        $I->cantSeeResponseContainsJson(['id' => 4001]);
        $I->cantSeeResponseContainsJson(['id' => 4003]);
        $I->cantSeeResponseContainsJson(['id' => 4004]);
        $I->cantSeeResponseContainsJson(['id' => 4006]);
        $I->cantSeeResponseContainsJson(['id' => 4007]);
    }

    public function privateIndexInstructorWithGroup(ApiTester $I)
    {
        $I->amBearerAuthenticated("TEACH2;VALID");
        $I->sendGet('/common/notifications');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseMatchesJsonType(self::NOTIFICATION_SCHEMA, '$.[*]');
        $I->seeResponseContainsJson(
            [
                [
                    'id' => 4005,
                    'message' => 'Test message 6.',
                    'dismissible' => true,
                ],
                [
                    'id' => 4006,
                    'message' => 'Test message 7.',
                    'dismissible' => false,
                ],
            ]
        );
        $I->cantSeeResponseContainsJson(['id' => 4000]); // dismissed by user
        $I->cantSeeResponseContainsJson(['id' => 4001]);
        $I->cantSeeResponseContainsJson(['id' => 4002]); // dismissed by user
        $I->cantSeeResponseContainsJson(['id' => 4003]);
        $I->cantSeeResponseContainsJson(['id' => 4004]);
        $I->cantSeeResponseContainsJson(['id' => 4007]);
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

    public function dismissNotDismissibleNotification(ApiTester $I)
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
                'dismissible' => true,
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
