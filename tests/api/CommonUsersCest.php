<?php

namespace app\tests\api;

use ApiTester;
use app\tests\unit\fixtures\AccessTokenFixture;
use app\tests\unit\fixtures\AuthAssignmentFixture;
use app\tests\unit\fixtures\UserFixture;
use Codeception\Util\HttpCode;

class CommonUsersCest
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

    public function searchNoResult(ApiTester $I)
    {
        $I->amBearerAuthenticated('TEACH2;VALID');
        $I->sendGet('/common/users/faculty?text=bla');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseContainsJson([]);
    }

    public function searchOneResultFaculty(ApiTester $I)
    {
        $I->amBearerAuthenticated('TEACH2;VALID');
        $I->sendGet('/common/users/faculty?text=thre');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseContainsJson(
            [
                [
                    'name' => 'Teacher Three',
                    'neptun' => 'TEACH3',
                    'id' => 1008
                ]
            ]
        );
    }

    public function searchOneResultStudent(ApiTester $I)
    {
        $I->amBearerAuthenticated('TEACH2;VALID');
        $I->sendGet('/common/users/student?text=stud01');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseContainsJson(
            [
                [
                    'name' => 'Student One',
                    'neptun' => 'STUD01',
                    'id' => 1001
                ]
            ]
        );
    }

    public function searchMultiResult(ApiTester $I)
    {
        $I->amBearerAuthenticated('TEACH2;VALID');
        $I->sendGet('/common/users/student?text=student t');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseContainsJson(
            [
                [
                    'name' => 'Student Two',
                    'neptun' => 'STUD02',
                    'id' => 1002
                ],
                [
                    'name' => 'Student Three',
                    'neptun' => 'STUD03',
                    'id' => 1003
                ]
            ]
        );
    }

    public function searchNotAuthenticated(ApiTester $I)
    {
        $I->sendGet('/common/users/faculty?text=stud');
        $I->seeResponseCodeIs(HttpCode::UNAUTHORIZED);
    }

    public function searchStudentNotAllowed(ApiTester $I)
    {
        $I->amBearerAuthenticated('STUD01;VALID');
        $I->sendGet('/common/users/student?text=stud');
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function searchTooShort(ApiTester $I)
    {
        $I->amBearerAuthenticated('TEACH2;VALID');
        $I->sendGet('/common/users/faculty?text=s');
        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);
    }
}
