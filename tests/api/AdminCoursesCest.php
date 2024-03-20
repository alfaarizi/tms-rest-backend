<?php

namespace app\tests\api;

use ApiTester;
use app\models\Course;
use app\models\InstructorCourse;
use app\tests\unit\fixtures\AccessTokenFixture;
use app\tests\unit\fixtures\CourseFixture;
use app\tests\unit\fixtures\InstructorCourseFixture;
use Codeception\Util\HttpCode;

class AdminCoursesCest
{
    public const COURSE_SCHEMA = [
        'id' => 'integer',
        'name' => 'string',
        'code' => 'string'
    ];

    public const USER_SCHEMA = [
        'id' => 'integer',
        'name' => 'string',
        'neptun' => 'string',
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
            'instructorcourses' => [
                'class' => InstructorCourseFixture::class,
            ],
        ];
    }

    public function _before(ApiTester $I)
    {
        $I->amBearerAuthenticated("BATMAN;12345");
    }

    public function index(ApiTester $I)
    {
        $I->sendGet('/admin/courses');
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

    public function view(ApiTester $I)
    {
        $I->sendGet('/admin/courses/4000');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseMatchesJsonType(self::COURSE_SCHEMA);
        $I->seeResponseContainsJson(
            [
                'id' => 4000,
                'name' => 'Java',
                'code' => 1,
            ]
        );
    }

    public function viewNotFound(ApiTester $I)
    {
        $I->sendGet('/admin/courses/0');
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function createValid(ApiTester $I)
    {
        $I->sendPost(
            '/admin/courses',
            [
                'name' => 'Created',
                'code' => '10'
            ]
        );
        $I->seeResponseCodeIs(HttpCode::CREATED);
        $I->seeResponseMatchesJsonType(self::COURSE_SCHEMA);
        $I->seeResponseContainsJson(
            [
                'name' => 'Created',
                'code' => '10'
            ]
        );
        $I->seeRecord(
            Course::class,
            [
                'name' => 'Created',
                'code' => '10'
            ]
        );
    }

    public function createInvalid(ApiTester $I)
    {
        $I->sendPost(
            '/admin/courses',
            [
                'name' => '',
                'code' => '10'
            ]
        );
        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->cantSeeRecord(
            Course::class,
            [
                'name' => '',
                'code' => '10'
            ]
        );
    }

    public function updateNotFound(ApiTester $I)
    {
        $I->sendPost('/admin/courses/0');
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function updateInvalid(ApiTester $I)
    {
        $I->sendPatch(
            '/admin/courses/4000',
            [
                'name' => '',
                'code' => '10'
            ]
        );
        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->seeRecord(
            Course::class,
            [
                'id' => 4000,
                'name' => 'Java',
                'code' => 1
            ]
        );
    }

    public function updateValid(ApiTester $I)
    {
        $I->sendPatch(
            '/admin/courses/4000',
            [
                'name' => 'Updated',
                'code' => 'Updated'
            ]
        );
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseContainsJson(
            [
                'id' => 4000,
                'name' => 'Updated',
                'code' => 'Updated'
            ]
        );
        $I->seeRecord(
            Course::class,
            [
                'id' => 4000,
                'name' => 'Updated',
                'code' => 'Updated'
            ]
        );
    }

    public function listLecturersCourseNotFound(ApiTester $I)
    {
        $I->sendGet('admin/courses/0/lecturers');
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function listLecturers(ApiTester $I)
    {
        $I->sendGet('admin/courses/4002/lecturers');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseMatchesJsonType(self::USER_SCHEMA, '$.[*]');
        $I->seeResponseContainsJson(
            [
                [
                    'id' => 1006,
                    'neptun' => 'TEACH1',
                    'name' => 'Teacher One',
                ],
                [
                    'id' => 1007,
                    'neptun' => 'TEACH2',
                    'name' => 'Teacher Two',
                ],
            ]
        );
    }

    public function deleteLecturerCourseNotFound(ApiTester $I)
    {
        $I->sendDelete('/admin/courses/0/lecturers/7');
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function deleteLecturerNotFoundForCourse(ApiTester $I)
    {
        $I->sendDelete('/admin/courses/4000/lecturers/1006');
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function deleteLecturer(ApiTester $I)
    {
        $I->sendDelete('/admin/courses/4000/lecturers/1007');
        $I->seeResponseCodeIs(HttpCode::NO_CONTENT);
        $I->cantSeeRecord(
            InstructorCourse::class,
            [
                'userID' => 1007,
                'courseID' => 4000,
            ]
        );
    }

    public function addLecturerCourseNotFound(ApiTester $I)
    {
        $I->sendPost('/admin/courses/0/lecturers');
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function addLecturerInvalidBody(ApiTester $I)
    {
        $I->sendPost('/admin/courses/4000/lecturers', []);
        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->seeResponseMatchesJsonType(['string'], '$.[*]');
    }

    public function addLecturer(ApiTester $I)
    {
        $I->sendPost(
            '/admin/courses/4000/lecturers',
            [
                'neptunCodes' => ['TEACH0', 'TEACH1', 'TEACH2', 'TEACH3']
            ]
        );
        $I->seeResponseCodeIs(HttpCode::MULTI_STATUS);

        $I->seeRecord(InstructorCourse::class, ['userID' => 1006, 'courseID' => 4000]);
        $I->seeRecord(InstructorCourse::class, ['userID' => 1007, 'courseID' => 4000]);
        $I->seeRecord(InstructorCourse::class, ['userID' => 1008, 'courseID' => 4000]);

        $I->seeResponseContainsJson(
            [
                'addedUsers' => [
                    ['neptun' => 'TEACH1'],
                    ['neptun' => 'TEACH3'],
                ],
                'failed' => [
                    ['neptun' => 'TEACH0'],
                    ['neptun' => 'TEACH2'],
                ],
            ]
        );

        $I->seeResponseMatchesJsonType(
            [
                self::USER_SCHEMA
            ],
            '$.[addedUsers]'
        );

        $I->seeResponseMatchesJsonType(
            [
                [
                    'neptun' => 'string',
                    'cause' => 'string|array'
                ]
            ],
            '$.[failed]'
        );

        $I->seeEmailIsSent(2);
    }
}
