<?php

namespace app\tests\api;

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
        'userCode' => 'string',
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
        $I->sendGet('/instructor/groups/2007/instructors');
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function listInstructors(ApiTester $I)
    {
        $I->sendGet('/instructor/groups/2006/instructors');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseMatchesJsonType(self::USER_SCHEMA, '$.[*]');
        $I->seeResponseContainsJson(
            [
                ['userCode' => 'TEACH2'],
                ['userCode' => 'TEACH3'],
                ['userCode' => 'TEACH4'],
            ]
        );
        $I->cantSeeResponseContainsJson([['userCode' => 'TEACH1']]);
        $I->cantSeeResponseContainsJson([['userCode' => 'TEACH5']]);
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
        $I->sendDelete('/instructor/groups/2010/instructors/1007');
        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);
        $I->seeResponseContainsJson(
            [
                'message' => "You can't modify a group from a previous semester!",
            ]
        );
        $I->seeRecord(
            InstructorGroup::class,
            [
                'groupID' => 2010,
                'userID' => 1007
            ]
        );
    }

    public function deleteInstructorsWithoutPermission(ApiTester $I)
    {
        $I->sendDelete('/instructor/groups/2007/instructors/1009');
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
        $I->seeRecord(
            InstructorGroup::class,
            [
                'groupID' => 2007,
                'userID' => 1009
            ]
        );
    }

    public function deleteLastInstructor(ApiTester $I)
    {
        $I->sendDelete('/instructor/groups/2000/instructors/1007');
        $I->seeResponseCodeIs(HttpCode::NO_CONTENT);
        $I->cantSeeRecord(
            InstructorGroup::class,
            [
                'groupID' => 2000,
                'userID' => 1007
            ]
        );
    }

    public function deleteInstructor(ApiTester $I)
    {
        $I->sendDelete('/instructor/groups/2004/instructors/1009');
        $I->seeResponseCodeIs(HttpCode::NO_CONTENT);
        $I->cantSeeRecord(
            InstructorGroup::class,
            [
                'groupID' => 2004,
                'userID' => 1009
            ]
        );
    }

    public function addInstructorsGroupNotFound(ApiTester $I)
    {
        $I->sendPost(
            '/instructor/groups/0/instructors',
            [
                'userCodes' => ['TEACH02']
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
                'userCodes' => ['TEACH02']
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
            '/instructor/groups/2007/instructors',
            [
                'userCodes' => ['TEACH02']
            ]
        );
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function addInstructorsPreviousSemester(ApiTester $I)
    {
        $I->sendPost(
            '/instructor/groups/2010/instructors',
            [
                'userCodes' => ['TEACH02']
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
            '/instructor/groups/2000/instructors',
            []
        );
        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->seeResponseMatchesJsonType(['string'], '$.[*]');
    }

    public function addInstructorValid(ApiTester $I)
    {
        $I->sendPost(
            '/instructor/groups/2000/instructors',
            [
                'userCodes' => ['TEACH0', 'TEACH1', 'TEACH2', 'TEACH3']
            ]
        );
        $I->seeResponseCodeIs(HttpCode::MULTI_STATUS);

        $I->seeRecord(InstructorGroup::class, ['userID' => 1006, 'groupID' => 2000]);
        $I->seeRecord(InstructorGroup::class, ['userID' => 1007, 'groupID' => 2000]);
        $I->seeRecord(InstructorGroup::class, ['userID' => 1008, 'groupID' => 2000]);

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
