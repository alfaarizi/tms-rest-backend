<?php

namespace tests\api;

use ApiTester;
use Yii;
use app\models\InstructorGroup;
use app\tests\unit\fixtures\AccessTokenFixture;
use app\tests\unit\fixtures\GroupFixture;
use app\tests\unit\fixtures\InstructorCourseFixture;
use app\tests\unit\fixtures\InstructorGroupFixture;
use app\tests\unit\fixtures\TaskFixture;
use Codeception\Util\HttpCode;

class InstructorGroupsInstructorsCest
{
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
        ];
    }

    public function _before(ApiTester $I)
    {
        $I->amBearerAuthenticated("TEACH2;VALID");
        Yii::$app->language = 'en-US';
    }

    public function listInstructorsNotFound(ApiTester $I)
    {
        $I->sendGet('/instructor/groups/0/instructors');
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function listInstructorsWithoutPermission(ApiTester $I)
    {
        $I->sendGet('/instructor/groups/8/instructors');
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function listInstructors(ApiTester $I)
    {
        $I->sendGet('/instructor/groups/7/instructors');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseMatchesJsonType(self::USER_SCHEMA, '$.[*]');
        $I->seeResponseContainsJson(
            [
                ['neptun' => 'TEACH2'],
                ['neptun' => 'TEACH3'],
                ['neptun' => 'TEACH4'],
            ]
        );
        $I->cantSeeResponseContainsJson([['neptun' => 'TEACH1']]);
        $I->cantSeeResponseContainsJson([['neptun' => 'TEACH5']]);
    }

    public function deleteInstructorNotFound(ApiTester $I)
    {
        $I->sendDelete('/instructor/groups/0/instructors/8');
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    /*
    public function deleteInstructorFromCanvasCourse(ApiTester $I)
    {
        $I->sendDelete('/instructor/groups/6/instructors/10');
        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);
        $I->seeResponseContainsJson(
            [
                'message' => 'This operation cannot be performed on a canvas synchronized course!',
            ]
        );
        $I->seeRecord(
            InstructorGroup::class,
            [
                'groupID' => 6,
                'userID' => 10
            ]
        );
    }
    */

    public function deleteInstructorFromPreviousSemester(ApiTester $I)
    {
        $I->sendDelete('/instructor/groups/11/instructors/8');
        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);
        $I->seeResponseContainsJson(
            [
                'message' => "You can't modify a group from a previous semester!",
            ]
        );
        $I->seeRecord(
            InstructorGroup::class,
            [
                'groupID' => 11,
                'userID' => 8
            ]
        );
    }

    public function deleteInstructorsWithoutPermission(ApiTester $I)
    {
        $I->sendDelete('/instructor/groups/8/instructors/10');
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
        $I->seeRecord(
            InstructorGroup::class,
            [
                'groupID' => 8,
                'userID' => 10
            ]
        );
    }

    public function deleteLastInstructor(ApiTester $I)
    {
        $I->sendDelete('/instructor/groups/1/instructors/8');
        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);
        $I->seeResponseContainsJson(
            [
                'message' => 'Can not remove the last instructor.',
            ]
        );
        $I->seeRecord(
            InstructorGroup::class,
            [
                'groupID' => 1,
                'userID' => 8
            ]
        );
    }

    public function deleteInstructor(ApiTester $I)
    {
        $I->sendDelete('/instructor/groups/5/instructors/10');
        $I->seeResponseCodeIs(HttpCode::NO_CONTENT);
        $I->cantSeeRecord(
            InstructorGroup::class,
            [
                'groupID' => 5,
                'userID' => 10
            ]
        );
    }

    public function addInstructorsGroupNotFound(ApiTester $I)
    {
        $I->sendPost(
            '/instructor/groups/0/instructors',
            [
                'neptunCodes' => ['TEACH02']
            ]
        );
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    /*
    public function addInstructorsToCanvasCourse(ApiTester $I)
    {
        $I->sendPost(
            '/instructor/groups/6/instructors',
            [
                'neptunCodes' => ['TEACH02']
            ]
        );
        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);
        $I->seeResponseContainsJson(
            [
                'message' => 'This operation cannot be performed on a canvas synchronized course!',
            ]
        );
    }
    */

    public function addInstructorsWithoutPermission(ApiTester $I)
    {
        $I->sendPost(
            '/instructor/groups/8/instructors',
            [
                'neptunCodes' => ['TEACH02']
            ]
        );
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function addInstructorsPreviousSemester(ApiTester $I)
    {
        $I->sendPost(
            '/instructor/groups/11/instructors',
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

    public function addInstructorInvalid(ApiTester $I)
    {
        $I->sendPost(
            '/instructor/groups/1/instructors',
            []
        );
        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->seeResponseMatchesJsonType(['string'], '$.[*]');
    }

    public function addInstructorValid(ApiTester $I)
    {
        $I->sendPost(
            '/instructor/groups/1/instructors',
            [
                'neptunCodes' => ['TEACH0', 'TEACH1', 'TEACH2', 'TEACH3']
            ]
        );
        $I->seeResponseCodeIs(HttpCode::MULTI_STATUS);

        $I->seeRecord(InstructorGroup::class, ['userID' => 7, 'groupID' => 1]);
        $I->seeRecord(InstructorGroup::class, ['userID' => 8, 'groupID' => 1]);
        $I->seeRecord(InstructorGroup::class, ['userID' => 9, 'groupID' => 1]);

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
