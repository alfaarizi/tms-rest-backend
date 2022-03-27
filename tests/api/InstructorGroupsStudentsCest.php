<?php

namespace tests\api;

use ApiTester;
use Yii;
use app\models\Subscription;
use app\models\User;
use app\tests\unit\fixtures\AccessTokenFixture;
use app\tests\unit\fixtures\GroupFixture;
use app\tests\unit\fixtures\InstructorCourseFixture;
use app\tests\unit\fixtures\InstructorGroupFixture;
use app\tests\unit\fixtures\SubscriptionFixture;
use app\tests\unit\fixtures\TaskFixture;
use Codeception\Util\HttpCode;

class InstructorGroupsStudentsCest
{
    public const USER_SCHEMA = [
        'id' => 'integer',
        'name' => 'string|null',
        'neptun' => 'string',
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
            'instructorcourses' => [
                'class' => InstructorCourseFixture::class,
            ],
            'instructorctorgroups' => [
                'class' => InstructorGroupFixture::class,
            ],
            'subscriptions' => [
                'class' => SubscriptionFixture::class
            ]
        ];
    }

    public function _before(ApiTester $I)
    {
        $I->amBearerAuthenticated("TEACH2;VALID");
        Yii::$app->language = 'en-US';
    }

    public function listStudentsNotFound(ApiTester $I)
    {
        $I->sendGet('/instructor/groups/0/students');
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function listStudentsWithoutPermission(ApiTester $I)
    {
        $I->sendGet('/instructor/groups/2007/students');
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }


    public function listStudents(ApiTester $I)
    {
        $I->sendGet('/instructor/groups/2000/students');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseMatchesJsonType(self::USER_SCHEMA, '$.[*]');
        $I->seeResponseContainsJson(
            [
                ['neptun' => 'STUD01'],
                ['neptun' => 'STUD02'],
                ['neptun' => 'STUD03'],
            ]
        );
        $I->cantSeeResponseContainsJson([['neptun' => 'STUD04']]);
        $I->cantSeeResponseContainsJson([['neptun' => 'STUD05']]);
    }

    public function deleteStudentNotFound(ApiTester $I)
    {
        $I->sendDelete('/instructor/groups/0/students/1007');
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function deleteStudentFromCanvasCourse(ApiTester $I)
    {
        $I->sendDelete('/instructor/groups/2005/students/1001');
        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);
        $I->seeResponseContainsJson(
            [
                'message' => 'This operation cannot be performed on a canvas synchronized course!',
            ]
        );
        $I->seeRecord(
            Subscription::class,
            [
                'id' => 6,
            ]
        );
    }

    public function deleteStudentFromPreviousSemester(ApiTester $I)
    {
        $I->sendDelete('/instructor/groups/2010/students/1001');
        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);
        $I->seeResponseContainsJson(
            [
                'message' => "You can't modify a group from a previous semester!",
            ]
        );
        $I->seeRecord(
            Subscription::class,
            [
                'id' => 7
            ]
        );
    }

    public function deleteStudentWithoutPermission(ApiTester $I)
    {
        $I->sendDelete('/instructor/groups/2007/students/1000');
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
        $I->seeRecord(
            Subscription::class,
            [
                'id' => 4
            ]
        );
    }

    public function deleteStudent(ApiTester $I)
    {
        $I->seeRecord(
            Subscription::class,
            [
                'id' => 1,
            ]
        );
        $I->sendDelete('/instructor/groups/2000/students/1001');
        $I->seeResponseCodeIs(HttpCode::NO_CONTENT);
        $I->cantSeeRecord(
            Subscription::class,
            [
                'id' => 1,
            ]
        );
    }

    public function addStudentsGroupNotFound(ApiTester $I)
    {
        $I->sendPost(
            '/instructor/groups/0/students',
            [
                'neptunCodes' => ['STUD01']
            ]
        );
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function addStudentsToCanvasCourse(ApiTester $I)
    {
        $I->sendPost(
            '/instructor/groups/2005/students',
            [
                'neptunCodes' => ['STUD01']
            ]
        );
        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);
        $I->seeResponseContainsJson(
            [
                'message' => 'This operation cannot be performed on a canvas synchronized course!',
            ]
        );
    }

    public function addStudentsWithoutPermission(ApiTester $I)
    {
        $I->sendPost(
            '/instructor/groups/2007/students',
            [
                'neptunCodes' => ['STUD01']
            ]
        );
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function addStudentsPreviousSemester(ApiTester $I)
    {
        $I->sendPost(
            '/instructor/groups/2010/students',
            [
                'neptunCodes' => ['TEACH02']
            ]
        );
        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);
        $I->seeResponseContainsJson(
            [
                'message' => "You can't modify a group from a previous semester!",
            ]
        );
    }

    public function addStudentsInvalid(ApiTester $I)
    {
        $I->sendPost(
            '/instructor/groups/2000/students',
            []
        );
        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->seeResponseMatchesJsonType(['string'], '$.[*]');
    }


    public function addStudentsValid(ApiTester $I)
    {
        $I->sendPost(
            '/instructor/groups/2000/students',
            [
                'neptunCodes' => ['STUD00', 'STUD01', 'STUD02', 'STUD03', 'STUD04', 'STUD05']
            ]
        );
        $I->seeResponseCodeIs(HttpCode::MULTI_STATUS);


        $I->seeResponseContainsJson(
            [
                'addedUsers' => [
                    ['neptun' => 'stud00'],
                    ['neptun' => 'STUD04'],
                    ['neptun' => 'STUD05'],
                ],
                'failed' => [
                    ['neptun' => 'STUD01'],
                    ['neptun' => 'STUD02'],
                    ['neptun' => 'STUD03'],
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

        // For STUD05. STUD04 also has an official email address set,
        // but has explicitly opted out of receiving notification emails.
        $I->seeEmailIsSent(1);

        // Check for exsisting users
        $I->seeRecord(Subscription::class, ['userID' => 1001, 'groupID' => 2000, 'semesterID' => 3001, 'isAccepted' => 1]);
        $I->seeRecord(Subscription::class, ['userID' => 1002, 'groupID' => 2000, 'semesterID' => 3001, 'isAccepted' => 1]);
        $I->seeRecord(Subscription::class, ['userID' => 1003, 'groupID' => 2000, 'semesterID' => 3001, 'isAccepted' => 1]);
        $I->seeRecord(Subscription::class, ['userID' => 1004, 'groupID' => 2000, 'semesterID' => 3001, 'isAccepted' => 1]);
        $I->seeRecord(Subscription::class, ['userID' => 1005, 'groupID' => 2000, 'semesterID' => 3001, 'isAccepted' => 1]);

        // Check for new user
        $newUser = $I->grabRecord(User::class, ['neptun' => 'stud00']);
        $I->seeRecord(
            Subscription::class,
            ['userID' => $newUser->id, 'groupID' => 2000, 'semesterID' => 3001, 'isAccepted' => 1]
        );
    }
}
