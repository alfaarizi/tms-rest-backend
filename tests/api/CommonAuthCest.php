<?php

namespace tests\api;

use ApiTester;
use app\models\AccessToken;
use app\models\User;
use app\tests\unit\fixtures\AccessTokenFixture;
use app\tests\unit\fixtures\AuthAssignmentFixture;
use app\tests\unit\fixtures\SemesterFixture;
use app\tests\unit\fixtures\UserFixture;
use Codeception\Util\HttpCode;

class CommonAuthCest
{
    public const USER_SCHEMA = [
        'id' => 'integer',
        'name' => 'string',
        'neptun' => 'string',
    ];

    public const TOKEN_SCHEMA = [
        'accessToken' => 'string',
        'imageToken' => 'string'
    ];

    public const SEMESTER_SCHEMA = [
        'id' => 'integer',
        'name' => 'string',
        'actual' => 'integer'
    ];

    public const USER_INFO_SCHEMA = [
        'id' => 'integer',
        'neptun' => 'string',
        'locale' => 'string',
        'isStudent' => 'boolean',
        'isFaculty' => 'boolean',
        'isAdmin' => 'boolean|string',
        'actualSemester' => self::SEMESTER_SCHEMA,
        'isAutoTestEnabled' => 'boolean',
        'isVersionControlEnabled' => 'boolean',
        'isCanvasEnabled' => 'boolean',
    ];

    public function _fixtures()
    {
        return [
            'accesstokens' => [
                'class' => AccessTokenFixture::class,
            ],
            'users' => [
                'class' => UserFixture::class,
            ],
            'authassignments' => [
                'class' => AuthAssignmentFixture::class
            ],
            'semesters' => [
                'class' => SemesterFixture::class
            ]
        ];
    }

    public function updateUserLocale(ApiTester $I)
    {
        $I->amBearerAuthenticated("BATMAN;12345");
        $I->sendPut(
            "/common/auth/update-user-locale",
            ['locale' => 'hu']
        );
        $I->seeResponseCodeIs(HttpCode::NO_CONTENT);
        $I->seeRecord(
            User::class,
            [
                "neptun" => "BATMAN",
                "locale" => "hu"
            ]
        );
    }

    public function mockLoginInvalid(ApiTester $I)
    {
        $I->sendPost(
            "/common/auth/mock-login",
            [
                'neptun' => 'batman',
                'name' => 'Bruce Wayne',
            ]
        );
        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->seeResponseMatchesJsonType(['string'], '$.[*]');
    }

    public function mockLoginExisting(ApiTester $I)
    {
        // Do no set locale from header
        $I->haveHttpHeader("Accept-Language", "hu");
        $I->sendPost(
            "/common/auth/mock-login",
            [
                'neptun' => 'batman',
                'name' => 'Bruce Wayne',
                'email' => 'updated@nanana.hu',
                'isStudent' => true,
                'isTeacher' => true,
                'isAdmin' => true
            ]
        );
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseMatchesJsonType(self::TOKEN_SCHEMA);
        $I->seeResponseMatchesJsonType(self::USER_INFO_SCHEMA, '$.[userInfo]');
        $I->seeResponseContainsJson(
            [
                'userInfo' => [
                    'id' => 1000,
                    'neptun' => 'BATMAN',
                    'locale' => 'en-US',
                    'isStudent' => true,
                    'isFaculty' => true,
                    'isAdmin' => true,
                    'actualSemester' => [
                        'id' => 3001,
                        'name' => '2018/19/2',
                        'actual' => 1,
                    ],
                    'isAutoTestEnabled' => true,
                    'isVersionControlEnabled' => false,
                    'isCanvasEnabled' => true
                ]
            ]
        );
        $I->seeRecord(User::class, ["id" => 1000, "email" => "updated@nanana.hu"]);
    }

    public function mockLoginNewUser(ApiTester $I)
    {
        // Set locale from header
        $I->haveHttpHeader("Accept-Language", "hu");
        $I->sendPost(
            "/common/auth/mock-login",
            [
                'neptun' => 'new123',
                'name' => 'New User',
                'email' => 'new123@elte.hu',
                'isStudent' => true,
                'isTeacher' => false,
                'isAdmin' => false
            ]
        );
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseMatchesJsonType(self::TOKEN_SCHEMA);
        $I->seeResponseMatchesJsonType(self::USER_INFO_SCHEMA, '$.[userInfo]');
        $I->seeResponseContainsJson(
            [
                'userInfo' => [
                    'neptun' => 'new123',
                    'locale' => 'hu',
                    'isStudent' => true,
                    'isFaculty' => false,
                    'isAdmin' => false,
                    'actualSemester' => [
                        'id' => 3001,
                        'name' => '2018/19/2',
                        'actual' => 1,
                    ],
                    'isAutoTestEnabled' => true,
                    'isVersionControlEnabled' => false,
                    'isCanvasEnabled' => true
                ]
            ]
        );
        $I->seeRecord(
            User::class,
            [
                'neptun' => 'new123',
                'name' => 'New User',
                'email' => 'new123@elte.hu',
                'locale' => 'hu'
            ]
        );
    }

    public function mockLoginTokenGenerated(ApiTester $I)
    {
        $I->sendPost(
            "/common/auth/mock-login",
            [
                'neptun' => 'STUD05',
                'name' => 'Student Five',
                'email' => 'stud05@elte.hu',
                'isStudent' => true,
                'isTeacher' => false,
                'isAdmin' => false
            ]
        );
        $I->seeRecord(
            AccessToken::class,
            [
                'userID' => 1005
            ]
        );
    }

    public function getUserInfo(ApiTester $I)
    {
        $I->amBearerAuthenticated("BATMAN;12345");
        $I->sendGet("/common/auth/user-info");
        $I->seeResponseContainsJson(
            [
                'id' => 1000,
                'neptun' => 'BATMAN',
                'locale' => 'en-US',
                'isStudent' => false,
                'isFaculty' => false,
                'isAdmin' => true,
                'actualSemester' => [
                    'id' => 3001,
                    'name' => '2018/19/2',
                    'actual' => 1,
                ],
                'isAutoTestEnabled' => true,
                'isVersionControlEnabled' => false,
                'isCanvasEnabled' => true
            ]
        );
    }

    public function updateUserLocaleInvalid(ApiTester $I)
    {
        $I->amBearerAuthenticated("BATMAN;12345");
        $I->sendPut(
            "/common/auth/update-user-locale",
            ['locale' => 'invalid']
        );
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function logout(ApiTester $I)
    {
        $I->amBearerAuthenticated("BATMAN;12345");
        $I->sendPost("/common/auth/logout");
        $I->seeResponseCodeIs(HttpCode::NO_CONTENT);
        $I->cantSeeRecord(AccessToken::class, ["token" => "BATMAN;12345"]);
    }

    public function logoutFromAll(ApiTester $I)
    {
        $I->amBearerAuthenticated("STUD01;VALID");
        $I->sendPost("/common/auth/logout-from-all");
        $I->seeResponseCodeIs(HttpCode::NO_CONTENT);
        $I->cantSeeRecord(AccessToken::class, ["userID" => 2]);
    }
}
