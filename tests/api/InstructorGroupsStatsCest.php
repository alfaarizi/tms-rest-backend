<?php

namespace tests\api;

use ApiTester;
use Yii;
use app\tests\unit\fixtures\AccessTokenFixture;
use app\tests\unit\fixtures\GroupFixture;
use app\tests\unit\fixtures\InstructorCourseFixture;
use app\tests\unit\fixtures\InstructorGroupFixture;
use app\tests\unit\fixtures\StudentFilesFixture;
use app\tests\unit\fixtures\SubscriptionFixture;
use app\tests\unit\fixtures\TaskFixture;
use app\tests\unit\fixtures\UserFixture;
use Codeception\Util\HttpCode;

class InstructorGroupsStatsCest
{
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
            ],
            'studentfiles' => [
                'class' => StudentFilesFixture::class
            ],
            'users' => [
                'class' => UserFixture::class
            ]
        ];
    }

    public function _before(ApiTester $I)
    {
        $I->amBearerAuthenticated("TEACH2;VALID");
        Yii::$app->language = 'en-US';
    }

    public function groupStatsNotFound(ApiTester $I)
    {
        $I->sendGet("/instructor/groups/0/stats");
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function groupStatsWithoutPermission(ApiTester $I)
    {
        $I->sendGet("/instructor/groups/8/stats");
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function groupsStats(ApiTester $I)
    {
        $I->sendGet("/instructor/groups/1/stats");
        $I->seeResponseCodeIs(HttpCode::OK);

        $I->seeResponseMatchesJsonType(
            [
                'taskID' => 'integer',
                'name' => 'string',
                'points' => 'array'
            ],
            '$.[*]'
        );

        $I->seeResponseMatchesJsonType(
            [
                'intime' => 'integer',
                'delayed' => 'integer',
                'missed' => 'integer',
            ],
            '$.[*].[submitted]'
        );

        $I->seeResponseContainsJson(
            [
                [
                    'taskID' => 1,
                    'name' => 'Task 1',
                    'points' => [],
                    'submitted' =>
                        [
                            'intime' => 0,
                            'delayed' => 0,
                            'missed' => 3,
                        ],
                ],
                [
                    'taskID' => 2,
                    'name' => 'Task 2',
                    'points' => [4],
                    'submitted' =>
                        [
                            'intime' => 3,
                            'delayed' => 0,
                            'missed' => 0,
                        ],
                ],
                [
                    'taskID' => 3,
                    'name' => 'Task 3',
                    'points' => [5, 1],
                    'submitted' =>
                        [
                            'intime' => 0,
                            'delayed' => 2,
                            'missed' => 0,
                        ],
                ],
                [
                    'taskID' => 4,
                    'name' => 'Task 4',
                    'points' => [],
                    'submitted' =>
                        [
                            'intime' => 0,
                            'delayed' => 0,
                            'missed' => 0,
                        ],
                ],
            ]
        );
    }

    public function studentStatsGroupNotFound(ApiTester $I)
    {
        $I->sendGet("/instructor/groups/0/students/1/stats");
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function studentStatsStudentNotFound(ApiTester $I)
    {
        $I->sendGet("/instructor/groups/1/students/0/stats");
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function studentStatsWithoutPermission(ApiTester $I)
    {
        $I->sendGet("/instructor/groups/8/students/1/stats");
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function studentStats(ApiTester $I)
    {
        $I->sendGet("/instructor/groups/1/students/2/stats");
        $I->seeResponseCodeIs(HttpCode::OK);

        $I->seeResponseMatchesJsonType(
            [
                'taskID' => 'integer',
                'name' => 'string',
                'submittingTime' => 'string|null',
                'softDeadLine' => 'string|null',
                'hardDeadLine' => 'string',
                'user' => 'integer|null',
                'username' => 'string',
                'group' => 'array',
            ],
            "$.[*]"
        );

        $I->seeResponseContainsJson(
            $data = [
                [
                    'taskID' => 1,
                    'name' => 'Task 1',
                    'submittingTime' => null,
                    'softDeadLine' => null,
                    'user' => null,
                    'username' => 'Student One',
                    'group' => [],
                ],
                [
                    'taskID' => 2,
                    'name' => 'Task 2',
                    'user' => 4,
                    'username' => 'Student One',
                    'group' => [4],
                ],
                [
                    'taskID' => 3,
                    'name' => 'Task 3',
                    'user' => 5,
                    'username' => 'Student One',
                    'group' => [5, 1],
                ],
                [
                    'taskID' => 4,
                    'name' => 'Task 4',
                    'submittingTime' => null,
                    'softDeadLine' => null,
                    'user' => null,
                    'username' => 'Student One',
                    'group' => []
                ],
            ]
        );
    }
}
