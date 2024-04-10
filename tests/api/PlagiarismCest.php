<?php

namespace app\tests\api;

use ApiTester;
use Yii;
use app\models\Plagiarism;
use app\tests\doubles\NoopPlagiarismFinder;
use app\tests\unit\fixtures\AccessTokenFixture;
use app\tests\unit\fixtures\JPlagPlagiarismFixture;
use app\tests\unit\fixtures\MossPlagiarismFixture;
use app\tests\unit\fixtures\SemesterFixture;
use app\tests\unit\fixtures\UserFixture;
use Codeception\Util\HttpCode;
use Wikimedia\ScopedCallback;

class PlagiarismCest
{
    public const BASE_PLAGIARISM_SCHEMA = [
        'id' => 'integer',
        'semesterID' => 'integer',
        'name' => 'string',
        'description' => 'string|null',
        'url' => 'string|null',
        'typeSpecificData' => [
            'type' => 'string',
        ],
    ];

    public const MOSS_PLAGIARISM_SCHEMA = [
        'typeSpecificData' => [
            'type' => 'string',
            'ignoreThreshold' => 'integer',
        ],
    ] + PlagiarismCest::BASE_PLAGIARISM_SCHEMA;

    public const JPLAG_PLAGIARISM_SCHEMA = [
        'typeSpecificData' => [
            'type' => 'string',
            'tune' => 'integer',
        ],
    ] + PlagiarismCest::BASE_PLAGIARISM_SCHEMA;

    public function _fixtures()
    {
        return [
            'semesters' => [
                'class' => SemesterFixture::class,
            ],
            'users' => [
                'class' => UserFixture::class
            ],
            'plagiarisms_moss' => [
                'class' => MossPlagiarismFixture::class,
            ],
            'plagiarisms_jplag' => [
                'class' => JPlagPlagiarismFixture::class,
            ],
            'accesstokens' => [
                'class' => AccessTokenFixture::class,
            ]
        ];
    }

    public function _before(ApiTester $I)
    {
        $I->deleteDir(Yii::getAlias("@tmp"));
        $I->copyDir(codecept_data_dir('appdata_samples'), Yii::getAlias("@tmp"));
        $I->amBearerAuthenticated("TEACH2;VALID");
        Yii::$app->language = 'en-US';
    }

    /**
     * Temporarily configure JPlag and automagically remove the configuration
     * once not needed. Warning: the reference to the returned object needs to
     * be kept as long as the JPlag config is needed, it’s deconfigured immediately
     * once there’s no reference to the object!
     */
    private function _setJplag(): ScopedCallback
    {
        Yii::$app->params['jplag'] = [
            'jre' => 'java',
            'jar' => '/dev/null',
            'report-viewer' => 'https://jplag.github.io/JPlag/',
        ];
        // phpcs:ignore Squiz.Functions,Squiz.WhiteSpace,Generic.Formatting.DisallowMultipleStatements.SameLine
        return new ScopedCallback(static function () { unset(Yii::$app->params['jplag']); });
    }

    public function index(ApiTester $I)
    {
        $jplagConfig = $this->_setJplag();
        $I->sendGet('/instructor/plagiarism?semesterID=3001');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseMatchesJsonType(self::BASE_PLAGIARISM_SCHEMA, '$.[*]');
        $I->seeResponseContainsJson(
            [
                [
                    'id' => 4,
                    'semesterID' => 3001,
                    'name' => 'plagiarism4',
                    'description' => 'description4',
                    'url' => null,
                    'typeSpecificData' => [
                        'type' => 'moss',
                        'ignoreThreshold' => 10,
                    ],
                ],
                [
                    'id' => 3,
                    'semesterID' => 3001,
                    'name' => 'plagiarism3',
                    'description' => 'description3',
                    'url' => null,
                    'typeSpecificData' => [
                        'type' => 'moss',
                        'ignoreThreshold' => 5,
                    ],
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
        $I->seeResponseMatchesJsonType(self::MOSS_PLAGIARISM_SCHEMA);
        $I->seeResponseContainsJson(
            [
                'id' => 3,
                'semesterID' => 3001,
                'name' => 'plagiarism3',
                'description' => 'description3',
                'url' => null,
                'typeSpecificData' => [
                    'type' => 'moss',
                    'ignoreThreshold' => 5,
                ],
            ]
        );
    }

    public function viewDownloadedMoss(ApiTester $I)
    {
        $I->sendGet('/instructor/plagiarism/7');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseMatchesJsonType(self::MOSS_PLAGIARISM_SCHEMA);
        $I->seeResponseContainsJson(
            [
                'id' => 7,
                'semesterID' => 3001,
                'name' => 'plagiarism7',
                'description' => 'description7',
                'url' => '/index-test.php/instructor/plagiarism-result?id=7&token=ad9e9bcd00632c86b547a1db0f3c9502',
                'typeSpecificData' => [
                    'type' => 'moss',
                    'ignoreThreshold' => 10,
                ],
            ]
        );
    }

    public function viewDownloadedJplag(ApiTester $I)
    {
        $jplagConfig = $this->_setJplag();
        $I->sendGet('/instructor/plagiarism/9');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseMatchesJsonType(self::JPLAG_PLAGIARISM_SCHEMA);
        $I->seeResponseContainsJson(
            [
                'id' => 9,
                'semesterID' => 3001,
                'name' => 'plagiarism9',
                'description' => 'description9',
                'url' => 'https://jplag.github.io/JPlag/?file=http%3A%2F%2Flocalhost%2Findex-test.php%2Finstructor%2Fplagiarism-result%3Fid%3D9%26token%3Dad9e9bcd00632c86b547a1db0f3c9502',
                'typeSpecificData' => [
                    'type' => 'jplag',
                    'tune' => 1,
                ],
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
            'type' => 'moss',
            'selectedTasks' => [5000, 5001],
            'selectedStudents' => [1001, 1002],
            'ignoreThreshold' => 1,
            'description' => 'created'
        ];
        $I->sendPost('/instructor/plagiarism', $data);
        $I->seeResponseCodeIs(HttpCode::CREATED);
        $I->seeResponseMatchesJsonType(self::MOSS_PLAGIARISM_SCHEMA);
        $I->seeResponseContainsJson([
            'name' => 'Created',
            'description' => 'created',
            'url' => null,
            'typeSpecificData' => [
                'type' => 'moss',
                'ignoreThreshold' => 1,
            ],
        ]);
        $I->seeRecord(Plagiarism::class, [
            'name' => 'Created',
            'description' => 'created',
            'type' => 'moss',
        ]);
    }

    public function createWithoutJplag(ApiTester $I)
    {
        $data = [
            'name' => 'Created',
            'type' => 'jplag',
            'selectedTasks' => [5000, 5001],
            'selectedStudents' => [1001, 1002],
            'ignoreThreshold' => 1,
            'description' => 'created'
        ];
        $I->sendPost('/instructor/plagiarism', $data);
        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->seeResponseContainsJson(
            [
                'type' => [ 'Unsupported type. Supported plagiarism type: moss' ]
            ]
        );
    }

    public function createValidJplag(ApiTester $I)
    {
        $jplagConfig = $this->_setJplag();
        $data = [
            'name' => 'Created',
            'type' => 'jplag',
            'selectedTasks' => [5000, 5001],
            'selectedStudents' => [1001, 1002],
            'description' => 'created'
        ];
        $I->sendPost('/instructor/plagiarism', $data);
        $I->seeResponseCodeIs(HttpCode::CREATED);
        $I->seeResponseMatchesJsonType(self::JPLAG_PLAGIARISM_SCHEMA);
        $I->seeResponseContainsJson([
            'name' => 'Created',
            'description' => 'created',
            'url' => null,
            'typeSpecificData' => [
                'type' => 'jplag',
                'tune' => 0,
            ],
        ]);
        $I->seeRecord(Plagiarism::class, [
            'name' => 'Created',
            'description' => 'created',
            'type' => 'jplag',
        ]);
    }

    public function createWithBasefile(ApiTester $I)
    {
        $data = [
            'name' => 'Created',
            'type' => 'moss',
            'selectedTasks' => [5000, 5001],
            'selectedStudents' => [1001, 1002],
            'selectedBasefiles' => [6000],
            'ignoreThreshold' => 1,
            'description' => 'created'
        ];
        $I->sendPost('/instructor/plagiarism', $data);
        $I->seeResponseCodeIs(HttpCode::CREATED);
        $I->seeResponseMatchesJsonType(self::MOSS_PLAGIARISM_SCHEMA);
        $I->seeResponseContainsJson([
            'name' => 'Created',
            'description' => 'created',
            'url' => null,
            'typeSpecificData' => [
                'type' => 'moss',
                'ignoreThreshold' => 1,
            ],
        ]);
        $I->seeRecord(Plagiarism::class, [
            'name' => 'Created',
            'description' => 'created',
            'type' => 'moss',
        ]);
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
            'type' => 'moss',
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
            'type' => 'moss',
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
            'type' => 'moss',
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
            'type' => 'moss',
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
            'type' => 'moss',
            'selectedTasks' => [5000, 5001],
            'selectedStudents' => [1001, 1002],
            'ignoreThreshold' => 5,
            'description' => 'created'
        ];


        $I->sendPost('/instructor/plagiarism', $data);
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseMatchesJsonType(self::MOSS_PLAGIARISM_SCHEMA);
        $I->seeResponseContainsJson(
            [
                'id' => 3,
                'semesterID' => 3001,
                'name' => 'plagiarism3',
                'description' => 'description3',
                'url' => null,
                'typeSpecificData' => [
                    'type' => 'moss',
                    'ignoreThreshold' => 5,
                ],
            ]
        );

        $I->cantSeeRecord(
            Plagiarism::class,
            [
                'name' => 'Created',
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

        $I->seeResponseMatchesJsonType(self::MOSS_PLAGIARISM_SCHEMA);
        $I->seeResponseContainsJson(
            [
                'id' => 3,
                'semesterID' => 3001,
                'name' => 'Updated',
                'description' => 'description3',
                'url' => null,
                'typeSpecificData' => [
                    'type' => 'moss',
                    'ignoreThreshold' => 5, // can't modify ignoreThreshold
                ],
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

    public function run(ApiTester $I)
    {
        $I->sendPost('/instructor/plagiarism/3/run');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseMatchesJsonType(self::MOSS_PLAGIARISM_SCHEMA);
        $I->seeResponseContainsJson(
            [
                'id' => 3,
                'semesterID' => 3001,
                'name' => 'plagiarism3',
                'description' => 'description3',
                'url' => null,
                'typeSpecificData' => [
                    'type' => 'moss',
                    'ignoreThreshold' => 5,
                ],
            ]
        );
    }

    public function runNotFound(ApiTester $I)
    {
        $I->sendPost('/instructor/plagiarism/0/run');
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function runWithoutPermission(ApiTester $I)
    {
        $I->sendPost('/instructor/plagiarism/1/run');
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function runDisabled(ApiTester $I)
    {
        NoopPlagiarismFinder::$enabled = false;
        $I->sendPost('/instructor/plagiarism/3/run');
        $I->seeResponseCodeIs(HttpCode::NOT_IMPLEMENTED);
        NoopPlagiarismFinder::$enabled = true;
    }

    public function runFailing(ApiTester $I)
    {
        NoopPlagiarismFinder::$fails = true;
        $I->sendPost('/instructor/plagiarism/3/run');
        $I->seeResponseCodeIs(HttpCode::BAD_GATEWAY);
        $I->seeResponseContainsJson(
            [
                'message' => 'You rang?'
            ]
        );
    }
}
