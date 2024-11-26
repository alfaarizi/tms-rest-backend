<?php

namespace app\tests\api;

use ApiTester;
use app\models\TaskAccessTokens;
use app\tests\unit\fixtures\TaskAccessTokenFixture;
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
            'taskaccesstokens' => [
                'class' => TaskAccessTokenFixture::class,
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
        $I->sendGet('/student/tasks?groupID=2007');
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function index(ApiTester $I)
    {
        $I->sendGet('/student/tasks?groupID=2000');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseMatchesJsonType(self::TASK_SCHEMA, '$.[*].[*]');

        $I->seeResponseContainsJson([['id' => 5000]]);
        $I->seeResponseContainsJson([['id' => 5001]]);
        $I->seeResponseContainsJson([['id' => 5002]]);
        $I->seeResponseContainsJson([['id' => 5008]]);
        $I->seeResponseContainsJson([['id' => 5010]]);
        $I->seeResponseContainsJson([['id' => 5011]]);
        $I->seeResponseContainsJson([['id' => 5012]]);
        $I->seeResponseContainsJson([['id' => 5013]]);
        $I->seeResponseContainsJson([['id' => 5014]]);
        $I->seeResponseContainsJson([['id' => 5015]]);

        $I->cantSeeResponseContainsJson([['id' => 5003]]);
        $I->cantSeeResponseContainsJson([['id' => 5004]]);
        $I->cantSeeResponseContainsJson([['id' => 5005]]);
        $I->cantSeeResponseContainsJson([['id' => 5006]]);
    }

    public function viewNotFound(ApiTester $I)
    {
        $I->sendGet('/student/tasks/0');
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function viewNotAvailable(ApiTester $I)
    {
        $I->sendGet('/student/tasks/5003');
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function viewWithoutPermission(ApiTester $I)
    {
        $I->sendGet('/student/tasks/5007');
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function viewEntryProtectedTaskWithoutToken(ApiTester $I)
    {
        $I->amBearerAuthenticated("STUD02;VALID");
        $I->sendGet('/student/tasks/5019');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseMatchesJsonType(self::TASK_SCHEMA);
        $I->seeResponseContainsJson(
            [
                'id' => 5019,
                'name' => 'Task 20',
                'category' => 'Larger tasks',
                'translatedCategory' => 'Larger tasks',
                'description' =>  '',
                'softDeadline' => null,
                'available' => null,
                'semesterID' => 3001,
            ]
        );
    }

    public function view(ApiTester $I)
    {
        $I->sendGet('/student/tasks/5000');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseMatchesJsonType(self::TASK_SCHEMA);
        $I->seeResponseContainsJson(
            [
                'id' => 5000,
                'name' => 'Task 1',
                'category' => 'Larger tasks',
                'translatedCategory' => 'Larger tasks',
                'description' => 'Description',
                'softDeadline' => null,
                'hardDeadline' => '2021-03-08T10:00:00+01:00',
                'available' => null,
                'creatorName' => 'Teacher Two',
                'semesterID' => 3001,
            ]
        );
    }

    public function unlockEntryPasswordProtectedTask(ApiTester $I)
    {
        $I->amBearerAuthenticated("STUD02;VALID");
        $I->sendPost('student/tasks/5019/unlock', ['password' => 'password']);
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseMatchesJsonType(self::TASK_SCHEMA);

        $I->seeRecord(
            TaskAccessTokens::class,
            [
                'accessToken' => 'STUD02;VALID',
                'taskID' => 5019,
            ]
        );
    }

    public function unlockEntryPasswordProtectedTaskWithWrongPassword(ApiTester $I)
    {
        $I->amBearerAuthenticated("STUD02;VALID");
        $I->sendPost('student/tasks/5019/unlock', ['password' => 'password123']);
        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);

        $I->seeResponseContainsJson(['password' => ['Invalid password']]);


        $I->cantSeeRecord(
            TaskAccessTokens::class,
            [
                'accessToken' => 'STUD02;VALID',
                'taskID' => 5019,
            ]
        );
    }
}
