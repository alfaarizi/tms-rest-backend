<?php

namespace app\tests\api;

use ApiTester;
use app\tests\unit\fixtures\AccessTokenFixture;
use app\tests\unit\fixtures\CourseFixture;
use app\tests\unit\fixtures\GroupFixture;
use app\tests\unit\fixtures\InstructorCourseFixture;
use Codeception\Util\HttpCode;

class InstructorCoursesCest
{
    public const COURSE_SCHEMA = [
        'id' => 'integer',
        'name' => 'string',
        'code' => 'string'
    ];

    public function _fixtures()
    {
        return [
            'accesstokens' => [
                'class' => AccessTokenFixture::class,
            ],
            'courses' => [
                'class' => CourseFixture::class,
            ],
            'groups' => [
                'class' => GroupFixture::class,
            ],
            'instructorgroups' => [
                'class' => InstructorCourseFixture::class,
            ],
            'instructorcourses' => [
                'class' => InstructorCourseFixture::class,
            ],
        ];
    }

    public function _before(ApiTester $I)
    {
        $I->amBearerAuthenticated("TEACH1;VALID");
    }

    public function indexInstructorCourses(ApiTester $I)
    {
        $I->sendGet('/instructor/courses?instructor=true&forGroups=false');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseMatchesJsonType(self::COURSE_SCHEMA, '$.[*]');
        $I->seeResponseContainsJson(
            [
                [
                    'id' => 4001,
                    'name' => 'C++',
                    'code' => 2,
                ],
                [
                    'id' => 4002,
                    'name' => 'C#',
                    'code' => 3,
                ],
            ]
        );

        $I->cantSeeResponseContainsJson(
            [
                'id' => 4000,
            ]
        );
    }

    public function indexForGroups(ApiTester $I)
    {
        $I->sendGet('/instructor/courses?instructor=false&forGroups=true');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseMatchesJsonType(self::COURSE_SCHEMA, '$.[*]');
        $I->seeResponseContainsJson(
            [
                [
                    'id' => 4000,
                    'name' => 'Java',
                    'code' => 1,
                ],
                [
                    'id' => 4002,
                    'name' => 'C#',
                    'code' => 3,
                ],
            ]
        );

        $I->cantSeeResponseContainsJson(
            [
                'id' => 4001,
            ]
        );
    }

    public function indexAll(ApiTester $I)
    {
        $I->sendGet('/instructor/courses?instructor=true&forGroups=true');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseMatchesJsonType(self::COURSE_SCHEMA, '$.[*]');
        $I->seeResponseContainsJson(
            [
                [
                    'id' => 4000,
                    'name' => 'Java',
                    'code' => 1,
                ],
                [
                    'id' => 4001,
                    'name' => 'C++',
                    'code' => 2,
                ],
                [
                    'id' => 4002,
                    'name' => 'C#',
                    'code' => 3,
                ],
            ]
        );
    }
}
