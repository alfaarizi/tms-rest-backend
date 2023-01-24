<?php

namespace tests\api;

use ApiTester;
use app\models\PlagiarismBasefile;
use app\tests\unit\fixtures\AccessTokenFixture;
use app\tests\unit\fixtures\PlagiarismBasefileFixture;
use Codeception\Util\HttpCode;
use Yii;

/**
 * E2E tests for the PlagiarismBasefile controller.
 */
class InstructorPlagiarismBasefileCest
{
    public const BASEFILE_SCHEMA = [
        'id' => 'integer',
        'name' => 'string',
        'lastUpdateTime' => 'string',
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
            'basefiles' => [
                'class' => PlagiarismBasefileFixture::class,
            ],
        ];
    }

    public function _before(ApiTester $I)
    {
        $I->deleteDir(Yii::$app->params['data_dir']);
        $I->copyDir(codecept_data_dir('appdata_samples'), Yii::$app->params['data_dir']);
        $I->amBearerAuthenticated('TEACH2;VALID');
        Yii::$app->language = 'en-US';
    }

    public function _after(ApiTester $I)
    {
        $I->deleteDir(Yii::$app->params['data_dir']);
    }

    public function index(ApiTester $I)
    {
        $I->sendGet('/instructor/plagiarism-basefile');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseMatchesJsonType(self::BASEFILE_SCHEMA, '$.[*]');

        $I->seeResponseContainsJson(
            [
                [
                    'id' => 6000,
                    'name' => 'DelegateCommand.cs',
                ],
            ]
        );

        $I->cantSeeResponseContainsJson(['id' => 6001]);
    }

    public function listByTasks(ApiTester $I)
    {
        $I->sendPost('/instructor/plagiarism-basefile/by-tasks', ['ids' => [5000, 5001, 5005]]);
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseMatchesJsonType(self::BASEFILE_SCHEMA, '$.[*]');

        $I->seeResponseContainsJson(
            [
                [
                    'id' => 6000,
                    'name' => 'DelegateCommand.cs',
                ],
            ]
        );

        $I->cantSeeResponseContainsJson(['id' => 6001]);
    }

    public function downloadNotFound(ApiTester $I)
    {
        $I->sendGet('/instructor/plagiarism-basefile/0/download');
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function downloadWithoutPermission(ApiTester $I)
    {
        $I->sendGet('/instructor/plagiarism-basefile/6001/download');
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function download(ApiTester $I)
    {
        $I->sendGet('/instructor/plagiarism-basefile/6000/download');
        $I->seeResponseCodeIs(HttpCode::OK);

        $I->openFile(Yii::$app->params['data_dir'] . '/uploadedfiles/basefiles/6000');
        $I->seeFileContentsEqual(str_replace("\r", '', $I->grabResponse()));
    }

    public function createInvalidCourseID(ApiTester $I)
    {
        $I->sendPost(
            '/instructor/plagiarism-basefile',
            [
                'courseID' => 0,
            ],
            [
                'files' => [
                    codecept_data_dir('upload_samples/file1.txt'),
                ]
            ]
        );
        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->seeResponseMatchesJsonType(['string'], '$.[*]');
    }

    public function createNoFile(ApiTester $I)
    {
        $I->sendPost('/instructor/plagiarism-basefile', ['courseID' => 4000]);
        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->seeResponseMatchesJsonType(['string'], '$.[*]');
    }

    public function createWithoutPermission(ApiTester $I)
    {
        $I->sendPost(
            '/instructor/plagiarism-basefile',
            [
                'courseID' => 4003,
            ],
            [
                'files' => [
                    codecept_data_dir('upload_samples/file1.txt'),
                ]
            ]
        );
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function create(ApiTester $I)
    {
        $I->sendPost(
            '/instructor/plagiarism-basefile',
            [
                'courseID' => 4000,
            ],
            [
                'files' => [
                    codecept_data_dir('upload_samples/file1.txt'),
                    codecept_data_dir('upload_samples/file2.txt'),
                ]
            ]
        );
        $I->seeResponseCodeIs(HttpCode::MULTI_STATUS);
        $I->seeResponseContainsJson(
            [
                'uploaded' => [
                    ['name' => 'file1.txt'],
                    ['name' => 'file2.txt'],
                ],
                'failed' => [],
            ]
        );
        $I->seeResponseMatchesJsonType(self::BASEFILE_SCHEMA, '$.[uploaded].[*]');
    }

    public function deleteNotFound(ApiTester $I)
    {
        $I->sendDelete('/instructor/plagiarism-basefile/0');
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function deleteWithoutPermission(ApiTester $I)
    {
        $I->sendDelete('/instructor/plagiarism-basefile/6001');
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function delete(ApiTester $I)
    {
        $I->sendDelete('/instructor/plagiarism-basefile/6000');
        $I->seeResponseCodeIs(HttpCode::NO_CONTENT);
        $I->cantSeeFileFound('6000', Yii::$app->params['data_dir'] . '/uploadedfiles/basefiles');
        $I->cantSeeRecord(PlagiarismBasefile::class, ['id' => 6000]);
    }
}
