<?php

namespace app\tests\api;

use ApiTester;
use app\tests\unit\fixtures\AccessTokenFixture;
use app\tests\unit\fixtures\CourseCodeFixture;
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
            'codes' => 'array'
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
            ],
            'codes' => [
                'class' => CourseCodeFixture::class,
            ],
        ];
    }

    public function _before(ApiTester $I)
    {
        $I->amBearerAuthenticated("STUD01;VALID");
    }

    public function index(ApiTester $I)
    {
        $I->sendGet('/student/groups?semesterID=3001');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseMatchesJsonType(self::GROUP_SCHEMA, '$.[*]');
        $I->seeResponseMatchesJsonType(['string'], '$.[*].instructorNames');

        $I->seeResponseContainsJson(
            [
                [
                    "id" => 2000,
                    "number" => 1,
                    "course" => [
                        "id" => 4000,
                        "name" => "Java",
                        "codes" => ["1"]
                    ],
                    "instructorNames" => [
                        "Teacher Two"
                    ]
                ],
                [
                    "id" => 2001,
                    "number" => 2,
                    "course" => [
                        "id" => 4002,
                        "name" => "C#",
                        "codes" => ["3"]
                    ],
                    "instructorNames" => [
                        "Teacher One"
                    ]
                ],
                [
                    "id" => 2005,
                    "number" => 6,
                    "course" => [
                        "id" => 4000,
                        "name" => "Java",
                        "codes" => ["1"]
                    ],
                    "instructorNames" => [
                        "Teacher Two",
                        "Teacher Four"
                    ]
                ]
            ]
        );

        $I->cantSeeResponseContainsJson(['id' => 2002]);
        $I->cantSeeResponseContainsJson(['id' => 2003]);
        $I->cantSeeResponseContainsJson(['id' => 2004]);
        $I->cantSeeResponseContainsJson(['id' => 2006]);
        $I->cantSeeResponseContainsJson(['id' => 2007]);
        $I->cantSeeResponseContainsJson(['id' => 2008]);
        $I->cantSeeResponseContainsJson(['id' => 2009]);
        $I->cantSeeResponseContainsJson(['id' => 2010]);
    }

    public function viewNotFound(ApiTester $I)
    {
        $I->sendGet('/student/groups/0');
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function viewWithoutPermission(ApiTester $I)
    {
        $I->sendGet('/student/groups/2007');
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function view(ApiTester $I)
    {
        $I->sendGet('/student/groups/2000?expand=notes');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseMatchesJsonType(self::GROUP_SCHEMA);
        $I->seeResponseMatchesJsonType(['string'], '$.instructorNames');

        $I->seeResponseContainsJson(
            [
                "id" => 2000,
                "number" => 1,
                "course" => [
                    "id" => 4000,
                    "name" => "Java",
                    "codes" => ["1"]
                ],
                "instructorNames" => [
                    "Teacher Two"
                ],
                "canvasUrl" => null,
                "notes" => "subscription1"
            ]
        );
    }
}
