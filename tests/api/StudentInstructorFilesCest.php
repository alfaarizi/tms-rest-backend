<?php

namespace tests\api;

use ApiTester;
use Yii;
use app\tests\unit\fixtures\AccessTokenFixture;
use app\tests\unit\fixtures\GroupFixture;
use app\tests\unit\fixtures\InstructorFilesFixture;
use app\tests\unit\fixtures\SubscriptionFixture;
use app\tests\unit\fixtures\TaskFixture;
use app\tests\unit\fixtures\UserFixture;
use Codeception\Util\HttpCode;

class StudentInstructorFilesCest
{
    public const INSTRUCTOR_FILE_SCHEMA = [
        'id' => 'integer',
        'name' => 'string',
        'uploadTime' => 'string'
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
            'instructorfiles' => [
                'class' => InstructorFilesFixture::class
            ]
        ];
    }

    public function _before(ApiTester $I)
    {
        $I->deleteDir(Yii::$app->params['data_dir']);
        $I->copyDir(codecept_data_dir("appdata_samples"), Yii::$app->params['data_dir']);
        $I->amBearerAuthenticated("STUD01;VALID");
        Yii::$app->language = 'en-US';
    }

    public function _after(ApiTester $I)
    {
        $I->deleteDir(Yii::$app->params['data_dir']);
    }

    public function indexTaskNotFound(ApiTester $I)
    {
        $I->sendGet('/student/instructor-files', ['taskID' => 0]);
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function indexWithoutPermission(ApiTester $I)
    {
        $I->sendGet('/student/instructor-files', ['taskID' => 8]);
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function indexNotAvailable(ApiTester $I)
    {
        $I->sendGet('/student/instructor-files', ['taskID' => 4]);
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function index(ApiTester $I)
    {
        $I->sendGet('/student/instructor-files', ['taskID' => 1]);
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseMatchesJsonType(self::INSTRUCTOR_FILE_SCHEMA, '$.[*]');

        $I->seeResponseContainsJson(
            [
                [
                    'id' => 1,
                    'name' => 'file1.txt',
                    'uploadTime' => '2021-02-01T10:00:00+01:00',
                ],
                [
                    'id' => 2,
                    'name' => 'file2.txt',
                    'uploadTime' => '2021-02-02T10:00:00+01:00',
                ],
                [
                    'id' => 3,
                    'name' => 'file3.txt',
                    'uploadTime' => '2021-02-03T10:00:00+01:00',
                ],
            ]
        );

        $I->cantSeeResponseContainsJson(['id' => 4]);
        $I->cantSeeResponseContainsJson(['id' => 5]);
        $I->cantSeeResponseContainsJson(['id' => 6]);
        $I->cantSeeResponseContainsJson(['id' => 7]);
    }

    public function downloadNotFound(ApiTester $I)
    {
        $I->sendGet("/student/instructor-files/0/download");
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function downloadWithoutPermission(ApiTester $I)
    {
        $I->sendGet("/student/instructor-files/7/download");
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function downloadNotAvailable(ApiTester $I)
    {
        $I->sendGet("/student/instructor-files/4/download");
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function download(ApiTester $I)
    {
        $I->sendGet("/student/instructor-files/1/download");
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->openFile(Yii::$app->params['data_dir'] . "/uploadedfiles/1/file1.txt");
        $I->seeFileContentsEqual($I->grabResponse());
    }
}
