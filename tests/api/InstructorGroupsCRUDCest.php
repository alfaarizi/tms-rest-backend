<?php

namespace tests\api;

use ApiTester;
use Yii;
use app\models\Group;
use app\models\InstructorGroup;
use app\tests\unit\fixtures\AccessTokenFixture;
use app\tests\unit\fixtures\GroupFixture;
use app\tests\unit\fixtures\InstructorCourseFixture;
use app\tests\unit\fixtures\InstructorFilesFixture;
use app\tests\unit\fixtures\InstructorGroupFixture;
use app\tests\unit\fixtures\StudentFilesFixture;
use app\tests\unit\fixtures\TaskFixture;
use Codeception\Util\HttpCode;

class InstructorGroupsCRUDCest
{
    public const COURSE_SCHEMA = [
        'id' => 'integer',
        'name' => 'string',
        'code' => 'string'
    ];

    public const GROUP_SCHEMA = [
        'id' => 'integer',
        'number' => 'integer|null',
        'course' => self::COURSE_SCHEMA,
        'isExamGroup' => 'integer',
        'semesterID' => 'integer'
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
            'tasks' => [
                'class' => TaskFixture::class,
            ],
            'studentfiles' => [
                'class' => StudentFilesFixture::class
            ],
            'instructorfiles' => [
                'class' => InstructorFilesFixture::class
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
        $I->deleteDir(Yii::getAlias("@appdata"));
        $I->copyDir(codecept_data_dir("appdata_samples"), Yii::getAlias("@appdata"));
        $I->amBearerAuthenticated("TEACH2;VALID");
        Yii::$app->language = 'en-US';
    }

    public function _after(ApiTester $I)
    {
        $I->deleteDir(Yii::getAlias("@appdata"));
    }


    public function index(ApiTester $I)
    {
        $I->sendGet('/instructor/groups?semesterID=3001');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseMatchesJsonType(self::GROUP_SCHEMA, '$.[*]');

        // Groups via InstructorGroups (semesterID=2)
        $I->seeResponseContainsJson(
            [
                ['id' => 2006]
            ]
        );

        // Groups via InstructorCourses (semesterID=2)
        $I->seeResponseContainsJson(
            [
                ['id' => 2000],
                ['id' => 2001],
                ['id' => 2002],
                ['id' => 2003],
                ['id' => 2005],
            ]
        );

        $I->cantSeeResponseContainsJson([['id' => 2007]]);
        $I->cantSeeResponseContainsJson([['id' => 2008]]);
        $I->cantSeeResponseContainsJson([['id' => 2009]]);
        $I->cantSeeResponseContainsJson([['id' => 2010]]);
    }

    public function viewNotFound(ApiTester $I)
    {
        $I->sendGet('/instructor/groups/0');
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function viewWithoutPermission(ApiTester $I)
    {
        $I->sendGet('/instructor/groups/2007');
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function view(ApiTester $I)
    {
        $I->sendGet('/instructor/groups/2000');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseMatchesJsonType(self::GROUP_SCHEMA);

        $I->seeResponseContainsJson(
            [
                'id' => 2000,
                'number' => '1',
                'course' => [
                    'id' => 4000,
                    'name' => 'Java',
                    'code' => '1'
                ],
                'isExamGroup' => '0',
                'semesterID' => 3001
            ]
        );
    }

    public function createWithoutPermission(ApiTester $I)
    {
        $I->sendPost(
            '/instructor/groups',
            [
                'number' => 100,
                'courseID' => 4001,
                'isExamGroup' => '1',
                'timezone' => 'Europe/Budapest',
            ]
        );
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function createInvalid(ApiTester $I)
    {
        $I->sendPost(
            '/instructor/groups',
            [
                'number' => 'One',
                'courseID' => 4000,
                'isExamGroup' => '1',
            ]
        );
        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->seeResponseMatchesJsonType(['string'], '$.[*]');
    }

    public function createValid(ApiTester $I)
    {
        $I->sendPost(
            '/instructor/groups',
            [
                'number' => 100,
                'courseID' => 4000,
                'isExamGroup' => '1',
                'timezone' => 'Europe/Budapest',
            ]
        );

        $I->seeResponseContainsJson(
            [
                'id' => 2011,
                'number' => '100',
                'course' => [
                    'id' => 4000,
                    'name' => 'Java',
                    'code' => '1'
                ],
                'isExamGroup' => '1',
                'semesterID' => 3001
            ]
        );

        $I->seeRecord(
            Group::class,
            [
                'id' => 2011,
                'number' => 100,
                'courseID' => 4000,
                'isExamGroup' => '1',
            ]
        );

        $I->seeRecord(
            InstructorGroup::class,
            [
                'userID' => 1007,
                'groupID' => 2011
            ]
        );
    }

    public function updateNotFound(ApiTester $I)
    {
        $I->sendPatch('/instructor/groups/0');
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function updateWithoutPermission(ApiTester $I)
    {
        $I->sendPatch('/instructor/groups/2007');
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function updateInvalid(ApiTester $I)
    {
        $I->sendPatch(
            '/instructor/groups/2000',
            [
                'number' => 'One',
                'isExamGroup' => '1',
            ]
        );
        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->seeResponseMatchesJsonType(['string'], '$.[*]');
    }

    public function updateFromPreviousSemester(ApiTester $I)
    {
        $I->sendPatch(
            '/instructor/groups/2010',
            [
                'number' => 111,
            ]
        );
        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);

        $I->seeRecord(
            Group::class,
            [
                'id' => 2010,
                'number' => 11,
            ]
        );
    }

    /*
    public function updateCanvasCourse(ApiTester $I)
    {
        $I->sendPatch(
            '/instructor/groups/6',
            [
                'number' => 111,
            ]
        );
        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);

        $I->seeRecord(
            Group::class,
            [
                'id' => 6,
                'number' => 6,
            ]
        );
    }
    */

    public function updateValid(ApiTester $I)
    {
        $I->sendPatch(
            '/instructor/groups/2000',
            [
                'number' => 111,
                'isExamGroup' => '1',
            ]
        );
        $I->seeResponseCodeIs(HttpCode::OK);

        $I->seeResponseContainsJson(
            [
                'id' => 2000,
                'number' => 111,
                'course' => [
                    'id' => '4000',
                    'name' => 'Java',
                    'code' => '1'
                ],
                'isExamGroup' => '1',
                'semesterID' => '3001',
                'timezone' => 'Europe/Budapest'
            ]
        );


        $I->seeRecord(
            Group::class,
            [
                'id' => 2000,
                'number' => 111,
                'courseID' => 4000,
                'isExamGroup' => 1,
                'timezone' => 'Europe/Budapest'
            ]
        );
    }

    public function deleteNotFound(ApiTester $I)
    {
        $I->sendDelete('/instructor/groups/0');
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function deleteWithoutPermission(ApiTester $I)
    {
        $I->sendDelete('/instructor/groups/2007');
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);

        $I->seeRecord(
            Group::class,
            [
                'id' => 2007,
            ]
        );
    }

    public function deleteFromPreviousSemester(ApiTester $I)
    {
        $I->sendDelete('/instructor/groups/2010');
        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);

        $I->seeRecord(
            Group::class,
            [
                'id' => 2010,
                'number' => 11,
            ]
        );
    }

    public function deleteCanvasCourse(ApiTester $I)
    {
        $I->sendDelete('/instructor/groups/2005');
        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);

        $I->seeRecord(
            Group::class,
            [
                'id' => 2005,
            ]
        );
    }

    public function delete(ApiTester $I)
    {
        $I->sendDelete('/instructor/groups/2004');
        $I->seeResponseCodeIs(HttpCode::NO_CONTENT);
        $I->cantSeeRecord(
            Group::class,
            [
                'id' => 2004,
            ]
        );
    }

    public function deleteWithTasks(ApiTester $I)
    {
        $I->sendDelete('/instructor/groups/2000');
        $I->seeResponseCodeIs(HttpCode::CONFLICT);
        $I->seeRecord(
            Group::class,
            [
                'id' => 2000,
            ]
        );
    }


    public function duplicateNotFound(ApiTester $I)
    {
        $I->sendPost('/instructor/groups/0/duplicate');
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function duplicateWithoutPermission(ApiTester $I)
    {
        $I->sendPost('/instructor/groups/2007/duplicate');
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function duplicateCanvasCourse(ApiTester $I)
    {
        $I->sendPost('/instructor/groups/2005/duplicate');
        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);
    }

    public function duplicate(ApiTester $I)
    {
        $I->sendPost('/instructor/groups/2000/duplicate');
        $I->seeResponseCodeIs(HttpCode::CREATED);
        $I->seeResponseMatchesJsonType(self::GROUP_SCHEMA);

        $original = $I->grabRecord(Group::class, ['id' => 2000]);
    }
}
