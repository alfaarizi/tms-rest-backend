<?php

namespace app\tests\api;

use ApiTester;
use app\models\Course;
use app\models\CourseCode;
use app\models\InstructorCourse;
use app\tests\unit\fixtures\AccessTokenFixture;
use app\tests\unit\fixtures\CourseCodeFixture;
use app\tests\unit\fixtures\CourseFixture;
use app\tests\unit\fixtures\InstructorCourseFixture;
use app\tests\unit\fixtures\UserFixture;
use Codeception\Util\HttpCode;

class AdminCoursesCest
{
    public const COURSE_SCHEMA = [
        'id' => 'integer',
        'name' => 'string',
        'codes' => 'array'
    ];

    public const USER_SCHEMA = [
        'id' => 'integer',
        'name' => 'string',
        'userCode' => 'string',
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
            'codes' => [
                'class' => CourseCodeFixture::class,
            ],
            'users' => [
                'class' => UserFixture::class,
            ]
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
                    'codes' => ['1'],
                ],
                [
                    'id' => 4001,
                    'name' => 'C++',
                    'codes' => ['2'],
                ],
                [
                    'id' => 4002,
                    'name' => 'C#',
                    'codes' => ['3'],
                ],
            ]
        );
    }

    public function view(ApiTester $I)
    {
        $I->sendGet('/instructor/courses/4000');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseMatchesJsonType(self::COURSE_SCHEMA);
        $I->seeResponseContainsJson(
            [
                'id' => 4000,
                'name' => 'Java',
                'codes' => [1],
            ]
        );
    }

    public function viewNotFound(ApiTester $I)
    {
        $I->sendGet('/instructor/courses/0');
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function createValid(ApiTester $I)
    {
        $I->sendPost(
            '/admin/courses',
            [
                'name' => 'Created',
                'codes' => ['10'],
                'lecturerUserCodes' => ['TEACH1', 'TEACH2']
            ]
        );
        $I->seeResponseCodeIs(HttpCode::CREATED);
        $I->seeResponseMatchesJsonType(self::COURSE_SCHEMA);
        $I->seeResponseContainsJson(
            [
                'name' => 'Created',
                'codes' => ['10']
            ]
        );
        $I->seeRecord(
            Course::class,
            [
                'name' => 'Created',
            ]
        );
        $I->seeRecord(
            CourseCode::class,
            [
                'code' => '10'
            ]
        );

        $I->seeEmailIsSent(2);
    }

    public function createInvalid(ApiTester $I)
    {
        $I->sendPost(
            '/admin/courses',
            [
                'name' => '',
                'codes' => ['10'],
                'lecturerUserCodes' => ['TEACH1']
            ]
        );
        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->cantSeeRecord(
            Course::class,
            [
                'name' => '',
            ]
        );

        $I->seeEmailIsSent(0);
    }

    public function createCourseWithNoLecturer(ApiTester $I)
    {
        $I->sendPost(
            '/admin/courses',
            [
                'name' => 'test',
                'codes' => ['10'],
                'lecturerUserCodes' => []
            ]
        );
        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->seeResponseContainsJson(
            [
                "lecturerUserCodes" => ['Lecturer User Codes cannot be blank.']
            ]
        );

        $I->seeEmailIsSent(0);
    }

    public function deleteLastLecturer(ApiTester $I)
    {
        $I->sendDelete('instructor/courses/4003/lecturers/1006');
        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);
        $I->seeResponseContainsJson(
            [
                'message' => 'Cannot remove last lecturer!'
            ]
        );
        $I->seeRecord(
            InstructorCourse::class,
            [
                'userID' => 1006,
                'courseID' => 4003
            ]
        );
    }

    public function updateNotFound(ApiTester $I)
    {
        $I->sendPost('/instructor/courses/0');
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function updateInvalid(ApiTester $I)
    {
        $I->sendPatch(
            '/instructor/courses/4000',
            [
                'name' => '',
                'codes' => ['10']
            ]
        );
        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->seeRecord(
            Course::class,
            [
                'id' => 4000,
                'name' => 'Java',
            ]
        );
        $I->seeRecord(
            CourseCode::class,
            [
                'courseId' => 4000,
                'code' => 1
            ]
        );
    }

    public function updateValid(ApiTester $I)
    {
        $I->sendPatch(
            '/instructor/courses/4000',
            [
                'name' => 'Updated',
                'codes' => ['Updated']
            ]
        );
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseContainsJson(
            [
                'id' => 4000,
                'name' => 'Updated',
                'codes' => ['Updated']
            ]
        );
        $I->seeRecord(
            Course::class,
            [
                'id' => 4000,
                'name' => 'Updated',
            ]
        );
        $I->seeRecord(
            CourseCode::class,
            [
                'code' => 'Updated'
            ]
        );
    }

    public function listLecturersCourseNotFound(ApiTester $I)
    {
        $I->sendGet('instructor/courses/0/lecturers');
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function listLecturers(ApiTester $I)
    {
        $I->sendGet('instructor/courses/4002/lecturers');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseMatchesJsonType(self::USER_SCHEMA, '$.[*]');
        $I->seeResponseContainsJson(
            [
                [
                    'id' => 1006,
                    'userCode' => 'TEACH1',
                    'name' => 'Teacher One',
                ],
                [
                    'id' => 1007,
                    'userCode' => 'TEACH2',
                    'name' => 'Teacher Two',
                ],
            ]
        );
    }

    public function deleteLecturerCourseNotFound(ApiTester $I)
    {
        $I->sendDelete('/instructor/courses/0/lecturers/7');
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function deleteLecturerNotFoundForCourse(ApiTester $I)
    {
        $I->sendDelete('/instructor/courses/4000/lecturers/1006');
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function deleteLecturer(ApiTester $I)
    {
        $I->sendDelete('/instructor/courses/4000/lecturers/1007');
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
        $I->sendPost('/instructor/courses/0/lecturers');
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function addLecturerInvalidBody(ApiTester $I)
    {
        $I->sendPost('/instructor/courses/4000/lecturers', []);
        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->seeResponseMatchesJsonType(['string'], '$.[*]');
    }

    public function addLecturer(ApiTester $I)
    {
        $I->sendPost(
            '/instructor/courses/4000/lecturers',
            [
                'userCodes' => ['TEACH0', 'TEACH1', 'TEACH2', 'TEACH3']
            ]
        );
        $I->seeResponseCodeIs(HttpCode::MULTI_STATUS);

        $I->seeRecord(InstructorCourse::class, ['userID' => 1006, 'courseID' => 4000]);
        $I->seeRecord(InstructorCourse::class, ['userID' => 1007, 'courseID' => 4000]);
        $I->seeRecord(InstructorCourse::class, ['userID' => 1008, 'courseID' => 4000]);

        $I->seeResponseContainsJson(
            [
                'addedUsers' => [
                    ['userCode' => 'TEACH1'],
                    ['userCode' => 'TEACH3'],
                ],
                'failed' => [
                    ['userCode' => 'TEACH0'],
                    ['userCode' => 'TEACH2'],
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
                    'userCode' => 'string',
                    'cause' => 'string|array'
                ]
            ],
            '$.[failed]'
        );

        $I->seeEmailIsSent(2);
    }
}
