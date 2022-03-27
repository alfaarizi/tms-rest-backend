<?php

namespace tests\api;

use ApiTester;
use Yii;
use app\models\Plagiarism;
use app\tests\unit\fixtures\AccessTokenFixture;
use app\tests\unit\fixtures\PlagiarismFixture;
use app\tests\unit\fixtures\SemesterFixture;
use app\tests\unit\fixtures\UserFixture;
use Codeception\Util\HttpCode;

class PlagiarismCest
{
    public const PLAGIARISM_SCHEMA = [
        'id' => 'integer',
        'semesterID' => 'integer',
        'name' => 'string',
        'description' => 'string|null',
        'response' => 'string|null',
        'ignoreThreshold' => 'integer|string'
    ];

    public function _fixtures()
    {
        return [
            'semesters' => [
                'class' => SemesterFixture::class,
            ],
            'users' => [
                'class' => UserFixture::class
            ],
            'plagiarism' => [
                'class' => PlagiarismFixture::class
            ],
            'accesstokens' => [
                'class' => AccessTokenFixture::class,
            ]
        ];
    }

    public function _before(ApiTester $I)
    {
        $I->amBearerAuthenticated("TEACH2;VALID");
        Yii::$app->language = 'en-US';
    }

    public function index(ApiTester $I)
    {
        $I->sendGet('/instructor/plagiarism?semesterID=3001');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseMatchesJsonType(self::PLAGIARISM_SCHEMA, '$.[*]');
        $I->seeResponseContainsJson(
            [
                [
                    'id' => 4,
                    'semesterID' => 3001,
                    'name' => 'plagiarism4',
                    'description' => 'description4',
                    'response' => null,
                    'ignoreThreshold' => 10
                ],
                [
                    'id' => 3,
                    'semesterID' => 3001,
                    'name' => 'plagiarism3',
                    'description' => 'description3',
                    'response' => null,
                    'ignoreThreshold' => 5
                ],
            ]
        );
        $I->cantSeeResponseContainsJson([['id' => 1]]);
        $I->cantSeeResponseContainsJson([['id' => 2]]);
    }

    public function view(ApiTester $I)
    {
        $I->sendGet('/instructor/plagiarism/3');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseMatchesJsonType(self::PLAGIARISM_SCHEMA);
        $I->seeResponseContainsJson(
            [
                'id' => 3,
                'semesterID' => 3001,
                'name' => 'plagiarism3',
                'description' => 'description3',
                'response' => null,
                'ignoreThreshold' => 5
            ]
        );
    }

    public function viewNotFound(ApiTester $I)
    {
        $I->sendGet('/instructor/plagiarism/0');
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function viewWithoutPermission(ApiTester $I)
    {
        $I->sendGet('/instructor/plagiarism/1');
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function createValid(ApiTester $I)
    {
        $data = [
            'name' => 'Created',
            'selectedTasks' => [5000, 5001],
            'selectedStudents' => [1001, 1002],
            'ignoreThreshold' => 1,
            'description' => 'created'
        ];
        $expectedData = [
            'name' => 'Created',
            'ignoreThreshold' => 1,
            'description' => 'created'
        ];
        $I->sendPost('/instructor/plagiarism', $data);
        $I->seeResponseCodeIs(HttpCode::CREATED);
        $I->seeResponseMatchesJsonType(self::PLAGIARISM_SCHEMA);
        $I->seeResponseContainsJson($expectedData);
        $I->seeRecord(Plagiarism::class, $expectedData);
    }

    public function createWithBasefile(ApiTester $I)
    {
        $data = [
            'name' => 'Created',
            'selectedTasks' => [5000, 5001],
            'selectedStudents' => [1001, 1002],
            'selectedBasefiles' => [6000],
            'ignoreThreshold' => 1,
            'description' => 'created'
        ];
        $expectedData = [
            'name' => 'Created',
            'ignoreThreshold' => 1,
            'description' => 'created'
        ];
        $I->sendPost('/instructor/plagiarism', $data);
        $I->seeResponseCodeIs(HttpCode::CREATED);
        $I->seeResponseMatchesJsonType(self::PLAGIARISM_SCHEMA);
        $I->seeResponseContainsJson($expectedData);
        $I->seeRecord(Plagiarism::class, $expectedData);
    }

    public function createInvalid(ApiTester $I)
    {
        $data = [
            'name' => 'Created',
        ];
        $I->sendPost('/instructor/plagiarism', $data);
        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->seeResponseMatchesJsonType(['string'], '$.[*]');
        $I->cantSeeRecord(Plagiarism::class, ['name' => 'Created']);
    }

    public function createOnlyOneStudent(ApiTester $I)
    {
        $data = [
            'name' => 'Created',
            'selectedTasks' => [5000, 5001],
            'selectedStudents' => [1001],
            'ignoreThreshold' => 1,
            'description' => 'created'
        ];
        $I->sendPost('/instructor/plagiarism', $data);
        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->seeResponseContainsJson(
            [
                'selectedStudents' => [ 'The Selected Students attribute should have multiple values.' ]
            ]
        );
    }

    public function createInvalidTask(ApiTester $I)
    {
        $data = [
            'name' => 'Created',
            'selectedTasks' => [0, 5001],
            'selectedStudents' => [1001, 1002],
            'ignoreThreshold' => 1,
            'description' => 'created'
        ];
        $I->sendPost('/instructor/plagiarism', $data);
        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->seeResponseContainsJson(
            [
                'selectedTasks' => [ 'Selected Tasks is invalid.' ],
            ]
        );
    }

    public function createInvalidUser(ApiTester $I)
    {
        $data = [
            'name' => 'Created',
            'selectedTasks' => [5000, 5001],
            'selectedStudents' => [0, 1001],
            'ignoreThreshold' => 1,
            'description' => 'created'
        ];
        $I->sendPost('/instructor/plagiarism', $data);
        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->seeResponseContainsJson(
            [
                'selectedStudents' => [ 'Selected Students is invalid.' ],
            ]
        );
    }

    public function createInvalidBasefile(ApiTester $I)
    {
        $data = [
            'name' => 'Created',
            'selectedTasks' => [5000, 5001],
            'selectedStudents' => [1001, 1002],
            'selectedBasefiles' => [0],
            'ignoreThreshold' => 1,
            'description' => 'created'
        ];
        $I->sendPost('/instructor/plagiarism', $data);
        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->seeResponseContainsJson(
            [
                'selectedBasefiles' => [ 'Selected Basefiles is invalid.' ],
            ]
        );
    }

    public function createExisting(ApiTester $I)
    {
        $data = [
            'name' => 'Created',
            'selectedTasks' => [5000, 5001],
            'selectedStudents' => [1001, 1002],
            'ignoreThreshold' => 5,
            'description' => 'created'
        ];


        $I->sendPost('/instructor/plagiarism', $data);
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseMatchesJsonType(self::PLAGIARISM_SCHEMA);
        $I->seeResponseContainsJson(
            [
                'id' => 3,
                'semesterID' => 3001,
                'name' => 'plagiarism3',
                'description' => 'description3',
                'response' => null,
                'ignoreThreshold' => 5
            ]
        );

        $I->cantSeeRecord(
            Plagiarism::class,
            [
                'name' => 'Created',
                'ignoreThreshold' => 10,
                'description' => 'created'
            ]
        );
    }

    public function updateValid(ApiTester $I)
    {
        $I->sendPatch(
            '/instructor/plagiarism/3',
            [
                'name' => 'Updated',
                'ignoreThreshold' => 1  // can't modify ignoreThreshold
            ]
        );
        $I->seeResponseCodeIs(HttpCode::OK);

        $I->seeResponseMatchesJsonType(self::PLAGIARISM_SCHEMA);
        $I->seeResponseContainsJson(
            [
                'id' => 3,
                'semesterID' => 3001,
                'name' => 'Updated',
                'description' => 'description3',
                'response' => null,
                'ignoreThreshold' => 5  // can't modify ignoreThreshold
            ]
        );

        $I->seeRecord(Plagiarism::class, ['name' => 'Updated']);
    }

    public function updateNotFound(ApiTester $I)
    {
        $I->sendPatch(
            '/instructor/plagiarism/0',
            [
                'name' => 'Updated',
            ]
        );
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function updateWithoutPermission(ApiTester $I)
    {
        $I->sendPatch(
            '/instructor/plagiarism/1',
            [
                'name' => 'Updated',
            ]
        );
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
        $I->cantSeeRecord(Plagiarism::class, ['name' => 'Updated']);
    }

    public function updatePreviousSemester(ApiTester $I)
    {
        $I->sendPatch(
            '/instructor/plagiarism/2',
            [
                'name' => 'Updated',
            ]
        );
        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);
        $I->seeResponseContainsJson(
            [
                'message' => "You can't modify a request from a previous semester!"
            ]
        );
        $I->cantSeeRecord(Plagiarism::class, ['name' => 'Updated']);
    }

    public function delete(ApiTester $I)
    {
        $I->sendDelete('/instructor/plagiarism/3');
        $I->seeResponseCodeIs(HttpCode::NO_CONTENT);
        $I->cantSeeRecord(Plagiarism::class, ['id' => 3]);
    }

    public function deleteNotFound(ApiTester $I)
    {
        $I->sendDelete('/instructor/plagiarism/0');
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function deletePreviousSemester(ApiTester $I)
    {
        $I->sendDelete('/instructor/plagiarism/2');
        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);
        $I->seeResponseContainsJson(
            [
                'message' => "You can't modify a request from a previous semester!"
            ]
        );
        $I->seeRecord(Plagiarism::class, ['id' => 2]);
    }

    public function deleteWithoutPermission(ApiTester $I)
    {
        $I->sendDelete('/instructor/plagiarism/1');
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
        $I->seeRecord(Plagiarism::class, ['id' => 1]);
    }
}
