<?php

namespace app\tests\api;

use ApiTester;
use Yii;
use app\models\TestCase;
use app\tests\unit\fixtures\AccessTokenFixture;
use app\tests\unit\fixtures\TaskFixture;
use app\tests\unit\fixtures\TestCaseFixture;
use Codeception\Util\HttpCode;

class InstructorTestCasesCest
{
    public const TEST_CASE_SCHEMA = [
        'id' => 'integer|string',
        'input' => 'string',
        'output' => 'string',
        'taskID' => 'integer|string'
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
            'testcases' => [
                'class' => TestCaseFixture::class
            ]
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
        $I->sendGet("/instructor/test-cases?taskID=5000");
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseMatchesJsonType(self::TEST_CASE_SCHEMA, '$.[*]');
        $I->seeResponseContainsJson(
            [
                [
                    'id' => 1,
                    'input' => '1',
                    'output' => '1',
                    'arguments' => '1',
                    'taskID' => 5000
                ],
                [
                    'id' => 2,
                    'input' => '2',
                    'output' => '4',
                    'arguments' => '8',
                    'taskID' => 5000
                ],
            ]
        );
        $I->cantSeeResponseContainsJson([['id' => 3]]);
        $I->cantSeeResponseContainsJson([['id' => 4]]);
    }

    public function indexWithoutPermission(ApiTester $I)
    {
        $I->sendGet("/instructor/test-cases?taskID=5004");
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function indexTaskNotFound(ApiTester $I)
    {
        $I->sendGet("/instructor/test-cases?taskID=0");
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function createValid(ApiTester $I)
    {
        $data = [
            'taskID' => 5000,
            'arguments' => 'Created arguments',
            'input' => 'Created input',
            'output' => 'Created output'
        ];
        $I->sendPost(
            "/instructor/test-cases",
            $data
        );
        $I->seeResponseCodeIs(HttpCode::CREATED);
        $I->seeResponseMatchesJsonType(self::TEST_CASE_SCHEMA);
        $I->seeRecord(TestCase::class, $data);
    }

    public function createInvalid(ApiTester $I)
    {
        $data = [
            'taskID' => 0,
            'arguments' => 'Created arguments',
            'input' => 'Created input',
            'output' => 'Created output'
        ];
        $I->sendPost(
            "/instructor/test-cases",
            $data
        );
        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->seeResponseMatchesJsonType(['string'], '$.[*]');
        $I->cantSeeRecord(TestCase::class, $data);
    }

    public function createValidEmptyInput(ApiTester $I)
    {
        $data = [
            'taskID' => 5000,
            'arguments' => '',
            'input' => '',
            'output' => 'Created output'
        ];
        $I->sendPost(
            "/instructor/test-cases",
            $data
        );
        $I->seeResponseCodeIs(HttpCode::CREATED);
        $I->seeResponseMatchesJsonType(self::TEST_CASE_SCHEMA);
        $I->seeRecord(TestCase::class, $data);
    }

    public function createInvalidEmptyOutput(ApiTester $I)
    {
        $data = [
            'taskID' => 5000,
            'arguments' => 'Created arguments',
            'input' => 'Created input',
            'output' => ''
        ];
        $I->sendPost(
            "/instructor/test-cases",
            $data
        );
        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->seeResponseMatchesJsonType(['string'], '$.[*]');
        $I->cantSeeRecord(TestCase::class, $data);
    }

    public function createPreviousSemester(ApiTester $I)
    {
        $data = [
            'taskID' => 5005,
            'arguments' => 'Created arguments',
            'input' => 'Created input',
            'output' => 'Created output'
        ];
        $I->sendPost(
            "/instructor/test-cases",
            $data
        );
        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);
        $I->seeResponseContainsJson(
            [
                'message' => "You can't modify a task from a previous semester!"
            ]
        );
        $I->cantSeeRecord(TestCase::class, $data);
    }

    public function createWithoutPermission(ApiTester $I)
    {
        $data = [
            'taskID' => 5004,
            'arguments' => 'Created arguments',
            'input' => 'Created input',
            'output' => 'Created output'
        ];
        $I->sendPost(
            "/instructor/test-cases",
            $data
        );
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
        $I->cantSeeRecord(TestCase::class, $data);
    }

    public function updateValid(ApiTester $I)
    {
        $data = [
            'arguments' => 'Updated arguments',
            'input' => 'Updated input',
            'output' => 'Updated output',
            'taskID' => 5001 // can’t update taskID
        ];
        $expectedData = [
            'id' => 1,
            'arguments' => 'Updated arguments',
            'input' => 'Updated input',
            'output' => 'Updated output',
            'taskID' => 5000 // can’t update taskID
        ];
        $I->sendPatch(
            "/instructor/test-cases/1",
            $data
        );
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseMatchesJsonType(self::TEST_CASE_SCHEMA);
        $I->seeResponseContainsJson($expectedData);
        $I->seeRecord(TestCase::class, $expectedData);
    }

    public function updateNotFound(ApiTester $I)
    {
        $data = [
            'arguments' => 'Updated arguments',
            'input' => 'Updated input',
            'output' => 'Updated output',
        ];
        $I->sendPatch(
            "/instructor/test-cases/0",
            $data
        );
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
        $I->cantSeeRecord(TestCase::class, $data);
    }

    public function updatePreviousSemester(ApiTester $I)
    {
        $data = [
            'arguments' => 'Updated arguments',
            'input' => 'Updated input',
            'output' => 'Updated output',
        ];
        $I->sendPatch(
            "/instructor/test-cases/4",
            $data
        );
        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);
        $I->seeResponseContainsJson(
            [
                'message' => "You can't modify a task from a previous semester!"
            ]
        );
        $I->cantSeeRecord(TestCase::class, $data);
    }

    public function updateWithoutPermission(ApiTester $I)
    {
        $data = [
            'arguments' => 'Updated arguments',
            'input' => 'Updated input',
            'output' => 'Updated output',
        ];
        $I->sendPatch(
            "/instructor/test-cases/3",
            $data
        );
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
        $I->cantSeeRecord(TestCase::class, $data);
    }

    public function delete(ApiTester $I)
    {
        $I->sendDelete('/instructor/test-cases/1');
        $I->seeResponseCodeIs(HttpCode::NO_CONTENT);
        $I->cantSeeRecord(TestCase::class, ['id' => 1]);
    }

    public function deleteNotFound(ApiTester $I)
    {
        $I->sendDelete('/instructor/test-cases/0');
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function deletePreviousSemester(ApiTester $I)
    {
        $I->sendDelete('/instructor/test-cases/4');
        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);
        $I->seeResponseContainsJson(
            [
                'message' => "You can't modify a task from a previous semester!"
            ]
        );
        $I->seeRecord(TestCase::class, ['id' => 4]);
    }

    public function deleteWithoutPermission(ApiTester $I)
    {
        $I->sendDelete('/instructor/test-cases/3');
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
        $I->seeRecord(TestCase::class, ['id' => 3]);
    }

    public function exportTestCasesTaskNotFound(ApiTester $I)
    {
        $I->sendGet("/instructor/test-cases/export-test-cases?taskID=0&format=xlsx");
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function exportTestCasesWithoutPermission(ApiTester $I)
    {
        $I->sendGet("/instructor/test-cases/export-test-cases?taskID=5004&format=xlsx");
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function exportTestCasesUnsupportedFileFormat(ApiTester $I)
    {
        $I->sendGet("/instructor/test-cases/export-test-cases?taskID=5000&format=invalid");
        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);
    }

    public function exportTestCasesXlsx(ApiTester $I)
    {
        $I->sendGet("/instructor/test-cases/export-test-cases?taskID=5000&format=xlsx");
        $I->seeResponseCodeIs(HttpCode::OK);
    }

    public function exportTestCasesCsv(ApiTester $I)
    {
        $I->sendGet("/instructor/test-cases/export-test-cases?taskID=5000&format=csv");
        $I->seeResponseCodeIs(HttpCode::OK);
    }

    public function importTestCasesTaskNotFound(ApiTester $I)
    {
        $I->sendPost(
            "/instructor/test-cases/import-test-cases?taskID=0",
            [],
            ['file' => codecept_data_dir("upload_samples/test.csv")]
        );
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function importTestCasesWithoutPermission(ApiTester $I)
    {
        $I->sendPost(
            "/instructor/test-cases/import-test-cases?taskID=5004",
            [],
            ['file' => codecept_data_dir("upload_samples/test.csv")]
        );
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function importTestCasesValidationError(ApiTester $I)
    {
        $I->sendPost(
            "/instructor/test-cases/import-test-cases?taskID=5000",
            [],
            ['file' => codecept_data_dir("upload_samples/testInvalid.csv")]
        );
        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
    }

    public function importTestCasesInvalidFileFormat(ApiTester $I)
    {
        $I->sendPost(
            "/instructor/test-cases/import-test-cases?taskID=5000",
            [],
            ['file' => codecept_data_dir("upload_samples/testInvalid.xls")]
        );
        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);
    }

    public function importTestCasesXlsx(ApiTester $I)
    {
        $I->sendPost(
            "/instructor/test-cases/import-test-cases?taskID=5000",
            [],
            ['file' => codecept_data_dir("upload_samples/test.xlsx")]
        );
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseContainsJson(
            [
                [
                    'id' => 9,
                    'input' => '1',
                    'output' => '1',
                    'arguments' => '',
                    'taskID' => 5000
                ]
            ]
        );
    }

    public function importTestCasesCsv(ApiTester $I)
    {
        $I->sendPost(
            "/instructor/test-cases/import-test-cases?taskID=5000",
            [],
            ['file' => codecept_data_dir("upload_samples/test.csv")]
        );
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseContainsJson(
            [
                [
                    'id' => 9,
                    'input' => '1',
                    'output' => '1',
                    'arguments' => '',
                    'taskID' => 5000
                ],
                [
                    'id' => 10,
                    'input' => '2',
                    'output' => '2',
                    'arguments' => '',
                    'taskID' => 5000
                ],
            ]
        );
    }
}
