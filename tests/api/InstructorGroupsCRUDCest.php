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
        $I->deleteDir(Yii::$app->params['data_dir']);
        $I->copyDir(codecept_data_dir("appdata_samples"), Yii::$app->params['data_dir']);
        $I->amBearerAuthenticated("TEACH2;VALID");
        Yii::$app->language = 'en-US';
    }

    public function _after(ApiTester $I)
    {
        $I->deleteDir(Yii::$app->params['data_dir']);
    }


    public function index(ApiTester $I)
    {
        $I->sendGet('/instructor/groups?semesterID=2');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseMatchesJsonType(self::GROUP_SCHEMA, '$.[*]');

        // Groups via InstructorGroups (semesterID=2)
        $I->seeResponseContainsJson(
            [
                ['id' => 7]
            ]
        );

        // Groups via InstructorCourses (semesterID=2)
        $I->seeResponseContainsJson(
            [
                ['id' => 1],
                ['id' => 2],
                ['id' => 3],
                ['id' => 4],
                ['id' => 6],
            ]
        );

        $I->cantSeeResponseContainsJson([['id' => 8]]);
        $I->cantSeeResponseContainsJson([['id' => 9]]);
        $I->cantSeeResponseContainsJson([['id' => 10]]);
        $I->cantSeeResponseContainsJson([['id' => 11]]);
    }

    public function viewNotFound(ApiTester $I)
    {
        $I->sendGet('/instructor/groups/0');
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function viewWithoutPermission(ApiTester $I)
    {
        $I->sendGet('/instructor/groups/8');
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function view(ApiTester $I)
    {
        $I->sendGet('/instructor/groups/1');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseMatchesJsonType(self::GROUP_SCHEMA);

        $I->seeResponseContainsJson(
            [
                'id' => '1',
                'number' => '1',
                'course' => [
                    'id' => '1',
                    'name' => 'Java',
                    'code' => '1'
                ],
                'isExamGroup' => '0',
                'semesterID' => '2'
            ]
        );
    }

    public function createWithoutPermission(ApiTester $I)
    {
        $I->sendPost(
            '/instructor/groups',
            [
                'number' => 100,
                'courseID' => 2,
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
                'courseID' => 1,
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
                'courseID' => 1,
                'isExamGroup' => '1',
                'timezone' => 'Europe/Budapest',
            ]
        );

        $I->seeResponseContainsJson(
            [
                'id' => 12,
                'number' => '100',
                'course' => [
                    'id' => '1',
                    'name' => 'Java',
                    'code' => '1'
                ],
                'isExamGroup' => '1',
                'semesterID' => '2'
            ]
        );

        $I->seeRecord(
            Group::class,
            [
                'id' => 12,
                'number' => 100,
                'courseID' => 1,
                'isExamGroup' => '1',
            ]
        );

        $I->seeRecord(
            InstructorGroup::class,
            [
                'userID' => 8,
                'groupID' => 12
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
        $I->sendPatch('/instructor/groups/8');
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function updateInvalid(ApiTester $I)
    {
        $I->sendPatch(
            '/instructor/groups/1',
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
            '/instructor/groups/11',
            [
                'number' => 111,
            ]
        );
        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);

        $I->seeRecord(
            Group::class,
            [
                'id' => 11,
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
            '/instructor/groups/1',
            [
                'number' => 111,
                'isExamGroup' => '1',
            ]
        );
        $I->seeResponseCodeIs(HttpCode::OK);

        $I->seeResponseContainsJson(
            [
                'id' => 1,
                'number' => 111,
                'course' => [
                    'id' => '1',
                    'name' => 'Java',
                    'code' => '1'
                ],
                'isExamGroup' => '1',
                'semesterID' => '2',
                'timezone' => 'Europe/Budapest'
            ]
        );


        $I->seeRecord(
            Group::class,
            [
                'id' => 1,
                'number' => 111,
                'courseID' => 1,
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
        $I->sendDelete('/instructor/groups/8');
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);

        $I->seeRecord(
            Group::class,
            [
                'id' => 8,
            ]
        );
    }

    public function deleteFromPreviousSemester(ApiTester $I)
    {
        $I->sendDelete('/instructor/groups/11');
        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);

        $I->seeRecord(
            Group::class,
            [
                'id' => 11,
                'number' => 11,
            ]
        );
    }

    public function deleteCanvasCourse(ApiTester $I)
    {
        $I->sendDelete('/instructor/groups/6');
        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);

        $I->seeRecord(
            Group::class,
            [
                'id' => 6,
            ]
        );
    }

    public function delete(ApiTester $I)
    {
        $I->sendDelete('/instructor/groups/5');
        $I->seeResponseCodeIs(HttpCode::NO_CONTENT);
        $I->cantSeeRecord(
            Group::class,
            [
                'id' => 5,
            ]
        );
    }

    public function deleteWithTasks(ApiTester $I)
    {
        $I->sendDelete('/instructor/groups/1');
        $I->seeResponseCodeIs(HttpCode::CONFLICT);
        $I->seeRecord(
            Group::class,
            [
                'id' => 1,
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
        $I->sendPost('/instructor/groups/8/duplicate');
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function duplicateCanvasCourse(ApiTester $I)
    {
        $I->sendPost('/instructor/groups/6/duplicate');
        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);
    }

    public function duplicate(ApiTester $I)
    {
        $I->sendPost('/instructor/groups/1/duplicate');
        $I->seeResponseCodeIs(HttpCode::CREATED);
        $I->seeResponseMatchesJsonType(self::GROUP_SCHEMA);

        $original = $I->grabRecord(Group::class, ['id' => 1]);
    }
}
