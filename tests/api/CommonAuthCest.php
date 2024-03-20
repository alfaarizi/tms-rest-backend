<?php

namespace app\tests\api;

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
    public const TOKEN_SCHEMA = [
        'accessToken' => 'string',
        'imageToken' => 'string'
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
            ]
        );
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseMatchesJsonType(self::TOKEN_SCHEMA);
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
            ]
        );
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseMatchesJsonType(self::TOKEN_SCHEMA);
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
