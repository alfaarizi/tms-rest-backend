<?php

namespace tests\api;

use ApiTester;
use Yii;
use app\models\InstructorFile;
use app\tests\unit\fixtures\AccessTokenFixture;
use app\tests\unit\fixtures\GroupFixture;
use app\tests\unit\fixtures\InstructorFilesFixture;
use app\tests\unit\fixtures\SubscriptionFixture;
use app\tests\unit\fixtures\TaskFixture;
use app\tests\unit\fixtures\UserFixture;
use Codeception\Util\HttpCode;

class InstructorIFilesCest
{
    public const INSTRUCTOR_FILE_SCHEMA = [
        'id' => 'integer',
        'name' => 'string',
        'uploadTime' => 'string'
    ];

    public const UPLOADED_FAILED_SCHEMA = [
        'name' => 'string',
        'cause' => 'array'
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
        $I->amBearerAuthenticated("TEACH2;VALID");
        Yii::$app->language = 'en-US';
    }

    public function _after(ApiTester $I)
    {
        $I->deleteDir(Yii::$app->params['data_dir']);
    }

    public function indexTaskNotFound(ApiTester $I)
    {
        $I->sendGet('/instructor/instructor-files', ['taskID' => 0]);
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function indexWithoutPermission(ApiTester $I)
    {
        $I->sendGet('/instructor/instructor-files', ['taskID' => 5]);
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function index(ApiTester $I)
    {
        $I->sendGet('/instructor/instructor-files', ['taskID' => 1]);
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
        $I->sendGet("/instructor/instructor-files/0/download");
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function downloadWithoutPermission(ApiTester $I)
    {
        $I->sendGet("/instructor/instructor-files/7/download");
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function download(ApiTester $I)
    {
        $I->sendGet("/instructor/instructor-files/1/download");
        $I->seeResponseCodeIs(HttpCode::OK);

        $I->openFile(Yii::$app->params['data_dir'] . "/uploadedfiles/1/file1.txt");
        $I->seeFileContentsEqual($I->grabResponse());
    }

    public function create(ApiTester $I)
    {
        $I->sendPost(
            "/instructor/instructor-files",
            [
                "taskID" => 1,
            ],
            [
                "files" => [
                    codecept_data_dir("upload_samples/file1.txt"),
                    codecept_data_dir("upload_samples/file2.txt"),
                    codecept_data_dir("upload_samples/file3.txt"),
                    codecept_data_dir("upload_samples/file4.txt"),
                ]
            ]
        );
        $I->seeResponseCodeIs(HttpCode::MULTI_STATUS);
        $I->seeResponseContainsJson(
            [
                'uploaded' => [
                    ['name' => 'file4.txt']
                ],
                'failed' => [
                    ['name' => 'file1.txt'],
                    ['name' => 'file2.txt'],
                    ['name' => 'file3.txt'],
                ]
            ]
        );
        $I->seeResponseMatchesJsonType(self::INSTRUCTOR_FILE_SCHEMA, "$.[uploaded].[*]");
        $I->seeResponseMatchesJsonType(
            self::UPLOADED_FAILED_SCHEMA,
            "$.[failed].[*]"
        );
    }

    public function createInvalid(ApiTester $I)
    {
        $I->sendPost(
            "/instructor/instructor-files",
            [
                "taskID" => 0
            ]
        );
        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->seeResponseMatchesJsonType(['string'], '$.[*]');
    }

    public function createWithoutPermission(ApiTester $I)
    {
        $I->sendPost(
            "/instructor/instructor-files",
            [
                "taskID" => 5,
            ],
            [
                "files" => [
                    codecept_data_dir("upload_samples/file1.txt"),
                ]
            ]
        );
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function createPreviousSemester(ApiTester $I)
    {
        $I->sendPost(
            "/instructor/instructor-files",
            [
                "taskID" => 6,
            ],
            [
                "files" => [
                    codecept_data_dir("upload_samples/file1.txt"),
                ]
            ]
        );
        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);
    }

    public function deleteNotFound(ApiTester $I)
    {
        $I->sendDelete("/instructor/instructor-files/0");
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function deleteWithoutPermission(ApiTester $I)
    {
        $I->sendDelete("/instructor/instructor-files/7");
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function deletePreviousSemester(ApiTester $I)
    {
        $I->sendDelete("/instructor/instructor-files/6");
        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);
        $I->seeResponseContainsJson(
            [
                "message" => "You can't modify a task from a previous semester!"
            ]
        );
    }

    public function delete(ApiTester $I)
    {
        $I->sendDelete("/instructor/instructor-files/1");
        $I->seeResponseCodeIs(HttpCode::NO_CONTENT);
        $I->cantSeeFileFound("file1.txt", Yii::$app->params['data_dir'] . "/uploadedfiles/1");
        $I->cantSeeRecord(InstructorFile::class, ['id' => 1]);
    }
}
