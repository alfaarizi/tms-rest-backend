<?php

namespace tests\api;

use ApiTester;
use app\tests\unit\fixtures\AccessTokenFixture;
use app\tests\unit\fixtures\SemesterFixture;
use Codeception\Util\HttpCode;

class CommonSemestersCest
{
    public const SEMESTER_SCHEMA = [
        "id" => "integer",
        "name" => 'string',
        "actual" => 'integer'
    ];

    public function _fixtures()
    {
        return [
            'semesters' => [
                'class' => SemesterFixture::class,
            ],
            'accesstokens' => [
                'class' => AccessTokenFixture::class,
            ]
        ];
    }

    public function _before(ApiTester $I)
    {
        $I->amBearerAuthenticated("TEACH2;VALID");
    }

    // tests
    public function index(ApiTester $I)
    {
        $I->sendGet("/common/semesters");
        $I->seeResponseCodeIs(HttpCode::OK); // 200

        $I->seeResponseContainsJson(
            [
                [
                    "id" => 3000,
                    "name" => "2018/19/1",
                    "actual" => 0,
                ],
                [
                    "id" => 3001,
                    "name" => "2018/19/2",
                    "actual" => 1,
                ]
            ]
        );

        $I->seeResponseMatchesJsonType(self::SEMESTER_SCHEMA, '$.[*]');
    }
}
