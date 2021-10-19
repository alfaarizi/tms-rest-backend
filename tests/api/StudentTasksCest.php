<?php

namespace tests\api;

use ApiTester;
use Yii;
use app\tests\unit\fixtures\AccessTokenFixture;
use app\tests\unit\fixtures\GroupFixture;
use app\tests\unit\fixtures\SubscriptionFixture;
use app\tests\unit\fixtures\TaskFixture;
use app\tests\unit\fixtures\UserFixture;
use Codeception\Util\HttpCode;

class StudentTasksCest
{
    public const TASK_SCHEMA = [
        'id' => 'integer',
        'name' => 'string',
        'category' => 'string',
        'translatedCategory' => 'string',
        'description' => 'string',
        'softDeadline' => 'string|null',
        'hardDeadline' => 'string',
        'available' => 'string|null',
        'creatorName' => 'string'
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
            'users' => [
                'class' => UserFixture::class
            ],
            'subscriptions' => [
                'class' => SubscriptionFixture::class
            ],
        ];
    }

    public function _before(ApiTester $I)
    {
        $I->amBearerAuthenticated("STUD01;VALID");
        Yii::$app->language = 'en-US';
    }

    public function indexGroupNotFound(ApiTester $I)
    {
        $I->sendGet('/student/tasks?groupID=0');
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function indexWithoutPermission(ApiTester $I)
    {
        $I->sendGet('/student/tasks?groupID=8');
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function index(ApiTester $I)
    {
        $I->sendGet('/student/tasks?groupID=1');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseMatchesJsonType(self::TASK_SCHEMA, '$.[*].[*]');

        $I->seeResponseContainsJson([['id' => 1]]);
        $I->seeResponseContainsJson([['id' => 2]]);
        $I->seeResponseContainsJson([['id' => 3]]);
        $I->seeResponseContainsJson([['id' => 9]]);

        $I->cantSeeResponseContainsJson([['id' => 4]]);
        $I->cantSeeResponseContainsJson([['id' => 5]]);
        $I->cantSeeResponseContainsJson([['id' => 6]]);
        $I->cantSeeResponseContainsJson([['id' => 7]]);
    }

    public function viewNotFound(ApiTester $I)
    {
        $I->sendGet('/student/tasks/0');
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function viewNotAvailable(ApiTester $I)
    {
        $I->sendGet('/student/tasks/4');
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function viewWithoutPermission(ApiTester $I)
    {
        $I->sendGet('/student/tasks/8');
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function view(ApiTester $I)
    {
        $I->sendGet('/student/tasks/1');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseMatchesJsonType(self::TASK_SCHEMA);
        $I->seeResponseContainsJson(
            [
                'id' => 1,
                'name' => 'Task 1',
                'category' => 'Larger tasks',
                'translatedCategory' => 'Larger tasks',
                'description' => 'Description',
                'softDeadline' => null,
                'hardDeadline' => '2021-03-08T10:00:00+01:00',
                'available' => null,
                'creatorName' => 'Teacher Two',
                'semesterID' => 2,
            ]
        );
    }
}
