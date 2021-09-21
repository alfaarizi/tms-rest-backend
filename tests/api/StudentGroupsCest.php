<?php

namespace tests\api;

use ApiTester;
use app\tests\unit\fixtures\AccessTokenFixture;
use app\tests\unit\fixtures\GroupFixture;
use app\tests\unit\fixtures\SubscriptionFixture;
use app\tests\unit\fixtures\TaskFixture;
use Codeception\Util\HttpCode;

class StudentGroupsCest
{
    public const GROUP_SCHEMA = [
        'id' => 'integer',
        'number' => 'integer|null',
        'course' => [
            'id' => 'integer',
            'name' => 'string',
            'code' => 'string|null'
        ],
        'instructorNames' => 'array'
    ];

    public function _fixtures()
    {
        return [
            'accesstokens' => [
                'class' => AccessTokenFixture::class,
            ],
            'tasks' => [
                'class' => TaskFixture::class,
            ],
            'groups' => [
                'class' => GroupFixture::class
            ],
            'subscriptions' => [
                'class' => SubscriptionFixture::class
            ]
        ];
    }

    public function _before(ApiTester $I)
    {
        $I->amBearerAuthenticated("STUD01;VALID");
    }

    public function index(ApiTester $I)
    {
        $I->sendGet('/student/groups?semesterID=2');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseMatchesJsonType(self::GROUP_SCHEMA, '$.[*]');
        $I->seeResponseMatchesJsonType(['string'], '$.[*].instructorNames');

        $I->seeResponseContainsJson(
            [
                [
                    "id" => 1,
                    "number" => 1,
                    "course" => [
                        "id" => 1,
                        "name" => "Java",
                        "code" => "1"
                    ],
                    "instructorNames" => [
                        "Teacher Two"
                    ]
                ],
                [
                    "id" => 2,
                    "number" => 2,
                    "course" => [
                        "id" => 3,
                        "name" => "C#",
                        "code" => "3"
                    ],
                    "instructorNames" => [
                        "Teacher One"
                    ]
                ],
                [
                    "id" => 6,
                    "number" => 6,
                    "course" => [
                        "id" => 1,
                        "name" => "Java",
                        "code" => "1"
                    ],
                    "instructorNames" => [
                        "Teacher Two",
                        "Teacher Four"
                    ]
                ]
            ]
        );

        $I->cantSeeResponseContainsJson(['id' => 3]);
        $I->cantSeeResponseContainsJson(['id' => 4]);
        $I->cantSeeResponseContainsJson(['id' => 5]);
        $I->cantSeeResponseContainsJson(['id' => 7]);
        $I->cantSeeResponseContainsJson(['id' => 8]);
        $I->cantSeeResponseContainsJson(['id' => 9]);
        $I->cantSeeResponseContainsJson(['id' => 10]);
        $I->cantSeeResponseContainsJson(['id' => 11]);
    }

    public function viewNotFound(ApiTester $I)
    {
        $I->sendGet('/student/groups/0');
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function viewWithoutPermission(ApiTester $I)
    {
        $I->sendGet('/student/groups/8');
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function view(ApiTester $I)
    {
        $I->sendGet('/student/groups/1');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseMatchesJsonType(self::GROUP_SCHEMA);
        $I->seeResponseMatchesJsonType(['string'], '$.instructorNames');

        $I->seeResponseContainsJson(
            [
                "id" => 1,
                "number" => 1,
                "course" => [
                    "id" => 1,
                    "name" => "Java",
                    "code" => "1"
                ],
                "instructorNames" => [
                    "Teacher Two"
                ]
            ]
        );
    }
}
