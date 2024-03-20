<?php

namespace app\tests\api;

use ApiTester;
use app\models\User;
use app\tests\unit\fixtures\AccessTokenFixture;
use app\tests\unit\fixtures\AuthAssignmentFixture;
use app\tests\unit\fixtures\UserFixture;
use Codeception\Util\HttpCode;

class CommonUserSettingsCest
{
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
        ];
    }

    public function getSettings(ApiTester $I)
    {
        $I->amBearerAuthenticated('BATMAN;12345');
        $I->sendGet('/common/user-settings');
        $I->seeResponseContainsJson(
            [
                'name' => 'Bruce Wayne',
                'neptun' => 'BATMAN',
                'email' => 'batman@nanana.hu',
                'customEmail' => null,
                'locale' => 'en-US',
                'isStudent' => false,
                'isFaculty' => false,
                'isAdmin' => true,
                'customEmailConfirmed' => 0,
                'notificationTarget' => 'official',
            ]
        );
    }

    public function putSettings(ApiTester $I)
    {
        $I->amBearerAuthenticated('BATMAN;12345');
        $I->sendPut(
            '/common/user-settings',
            [
                'name' => 'Bud Spencer',
                'neptun' => 'BUD001',
                'email' => 'bud.spencer@example.org',
                'customEmail' => 'carlo.pedersoli@example.org',
                'locale' => 'hu',
                'customEmailConfirmed' => 1,
                'notificationTarget' => 'none',
            ]
        );
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseContainsJson(
            [
                'name' => 'Bruce Wayne',
                'neptun' => 'BATMAN',
                'email' => 'batman@nanana.hu',
                'customEmail' => 'carlo.pedersoli@example.org',
                'locale' => 'hu',
                'customEmailConfirmed' => false,
                'notificationTarget' => 'none',
            ]
        );
        $I->seeRecord(
            User::class,
            [
                'neptun' => 'BATMAN',
                'name' => 'Bruce Wayne',
                'email' => 'batman@nanana.hu',
                'customEmail' => 'carlo.pedersoli@example.org',
                'locale' => 'hu',
                'customEmailConfirmed' => 0,
                'notificationTarget' => 'none',
            ]
        );
    }

    public function putSettingsInvalid(ApiTester $I)
    {
        $I->amBearerAuthenticated('BATMAN;12345');
        $I->sendPut(
            '/common/user-settings',
            [
                'name' => 'Bud Spencer',
                'neptun' => 'BUD001',
                'email' => 'bud.spencer@example.org',
                'customEmail' => 'carlo.pedersoli@example.org',
                // `qaa` is a private use language tag per BCP 47 [1], section 2.2.1
                // [1] https://tools.ietf.org/rfc/bcp/bcp47.txt
                'locale' => 'qaa',
                'customEmailConfirmed' => 1,
                'notificationTarget' => 'none',
            ]
        );
        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->seeResponseContainsJson(['locale' => ['Locale is invalid.']]);
    }

    public function confirmEmail(ApiTester $I)
    {
        $I->amBearerAuthenticated('STUD01;VALID');
        $I->sendPost('/common/user-settings/confirm-email?code=MYCODE007');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseContainsJson(['currentUser' => true]);
    }

    public function confirmSomeoneElse(ApiTester $I)
    {
        $I->amBearerAuthenticated('BATMAN;12345');
        $I->sendPost('/common/user-settings/confirm-email?code=MYCODE007');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseContainsJson(['currentUser' => false]);
    }

    public function confirmLoggedOut(ApiTester $I)
    {
        $I->sendPost('/common/user-settings/confirm-email?code=MYCODE007');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseContainsJson(['currentUser' => false]);
    }

    public function confirmEmailInvalid(ApiTester $I)
    {
        $I->amBearerAuthenticated('STUD01;VALID');
        $I->sendPost('/common/user-settings/confirm-email?code=MYCODE006');
        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);
    }
}
