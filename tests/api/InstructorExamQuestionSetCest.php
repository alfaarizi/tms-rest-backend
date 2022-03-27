<?php

namespace tests\api;

use ApiTester;
use app\models\ExamQuestionSet;
use app\tests\unit\fixtures\AccessTokenFixture;
use app\tests\unit\fixtures\AnswerFixture;
use app\tests\unit\fixtures\QuestionFixture;
use app\tests\unit\fixtures\SubmittedAnswerFixture;
use app\tests\unit\fixtures\TestInstanceFixture;
use app\tests\unit\fixtures\TestInstanceQuestionFixture;
use Codeception\Util\HttpCode;
use Yii;

class InstructorExamQuestionSetCest
{
    public const QUESTION_SET_SCHEMA = [
        'id' => 'integer',
        'name' => 'string',
        'course' => [
            'id' => 'integer',
            'name' => 'string',
            'code' => 'string'
        ]
    ];

    public const IMAGE_SCHEMA = [
        'name' => 'string',
        'url' => 'string',
        'size' => 'integer'
    ];

    public function _fixtures()
    {
        return [
            'accesstokens' => [
                'class' => AccessTokenFixture::class,
            ],
            'testinstances' => [
                'class' => TestInstanceFixture::class,
            ],
            'testinstancequestion' => [
                'class' => TestInstanceQuestionFixture::class,
            ],
            "question" => [
                'class' => QuestionFixture::class
            ],
            "answers" => [
                'class' => AnswerFixture::class
            ],
            "submittedanswers" => [
                'class' => SubmittedAnswerFixture::class
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

    // tests
    public function index(ApiTester $I)
    {
        $I->sendGet("/instructor/exam-question-sets");
        $I->seeResponseCodeIs(HttpCode::OK);

        $I->seeResponseMatchesJsonType(self::QUESTION_SET_SCHEMA, "$.[*]");

        $I->seeResponseContainsJson(
            [
                [
                    'id' => 1,
                    'name' => 'Question set',
                    'course' => [
                        'id' => 4000,
                        'name' => 'Java',
                        'code' => '1'
                    ]
                ],
                [
                    'id' => 2,
                    'name' => 'Question set 2',
                    'course' => [
                        'id' => 4000,
                        'name' => 'Java',
                        'code' => '1'
                    ]
                ],
                [
                    'id' => 3,
                    'name' => 'Question set 3',
                    'course' => [
                        'id' => 4000,
                        'name' => 'Java',
                        'code' => '1'
                    ]
                ]
            ]
        );

        $I->cantSeeResponseContainsJson([['id' => 4]]);
    }

    public function viewNotFound(ApiTester $I)
    {
        $I->sendGet("/instructor/exam-question-sets/0");
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function viewPermissionDenied(ApiTester $I)
    {
        $I->sendGet("/instructor/exam-question-sets/4");
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function view(ApiTester $I)
    {
        $I->sendGet("/instructor/exam-question-sets/1");
        $I->seeResponseCodeIs(HttpCode::OK);

        $I->seeResponseMatchesJsonType(self::QUESTION_SET_SCHEMA);

        $I->seeResponseContainsJson(
            [
                'id' => 1,
                'name' => 'Question set',
                'course' => [
                    'id' => 4000,
                    'name' => 'Java',
                    'code' => '1'
                ]
            ]
        );
    }

    public function createInvalid(ApiTester $I)
    {
        $I->sendPost(
            "/instructor/exam-question-sets",
            [
                'courseID' => 0,
            ]
        );
        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->seeResponseMatchesJsonType(['string'], "$.[*]");
    }

    public function createWithoutPermission(ApiTester $I)
    {
        $I->sendPost(
            "/instructor/exam-question-sets",
            [
                'name' => 'Created Test',
                'courseID' => 4002,
            ]
        );
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function createValid(ApiTester $I)
    {
        $I->sendPost(
            "/instructor/exam-question-sets",
            [
                'name' => 'Created Test',
                'courseID' => 4000,
            ]
        );
        $I->seeResponseCodeIs(HttpCode::CREATED);
        $I->seeResponseMatchesJsonType(self::QUESTION_SET_SCHEMA);
        $I->seeResponseContainsJson(
            [
                'name' => 'Created Test',
                'course' => [
                    'id' => 4000,
                    'name' => 'Java',
                    'code' => '1'
                ]
            ]
        );

        $I->seeRecord(
            ExamQuestionSet::class,
            [
                'name' => 'Created Test',
                'courseID' => 4000,
            ]
        );
    }

    public function updateFinalized(ApiTester $I)
    {
        $I->sendPatch(
            "/instructor/exam-question-sets/1",
            [
                'name' => 'Updated Test',
                'courseID' => 4000,
            ]
        );
        $I->seeResponseCodeIs(HttpCode::CONFLICT);

        $I->seeRecord(
            ExamQuestionSet::class,
            [
                'id' => 1,
                'name' => 'Question set',
                'courseID' => 4000,
            ]
        );
    }

    public function updateNotFound(ApiTester $I)
    {
        $I->sendPatch(
            "/instructor/exam-question-sets/0",
            [
                'name' => 'Updated Test',
                'courseID' => 1,
            ]
        );
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function updateWithoutPermission(ApiTester $I)
    {
        $I->sendPatch(
            "/instructor/exam-question-sets/4",
            [
                'name' => 'Updated Test',
                'courseID' => 4002,
            ]
        );
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);

        $I->seeRecord(
            ExamQuestionSet::class,
            [
                'id' => 4,
                'name' => 'Question set 4',
                'courseID' => 4002,
            ]
        );
    }

    public function updateValid(ApiTester $I)
    {
        $I->sendPatch(
            "/instructor/exam-question-sets/3",
            [
                'name' => 'Updated Test',
                'courseID' => 4000,
            ]
        );
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseMatchesJsonType(self::QUESTION_SET_SCHEMA);
        $I->seeResponseContainsJson(
            [
                'id' => 3,
                'name' => 'Updated Test',
                'course' => [
                    'id' => 4000,
                    'name' => 'Java',
                    'code' => '1'
                ]
            ]
        );

        $I->seeRecord(
            ExamQuestionSet::class,
            [
                'id' => 3,
                'name' => 'Updated Test',
                'courseID' => 4000,
            ]
        );
    }

    public function updateInvalid(ApiTester $I)
    {
        $I->sendPatch(
            "/instructor/exam-question-sets/3",
            [
                'name' => 'Updated Test',
                'courseID' => 0,
            ]
        );
        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->seeResponseMatchesJsonType(['string'], "$.[*]");
    }

    public function deleteNotFound(ApiTester $I)
    {
        $I->sendDelete("/instructor/exam-question-sets/0");
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function deleteWithoutPermission(ApiTester $I)
    {
        $I->sendDelete("/instructor/exam-question-sets/4");
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
        $I->seeRecord(ExamQuestionSet::class, ['id' => 4]);
    }

    public function deleteFinalized(ApiTester $I)
    {
        $I->sendDelete("/instructor/exam-question-sets/1");
        $I->seeResponseCodeIs(HttpCode::CONFLICT);
        $I->seeRecord(ExamQuestionSet::class, ['id' => 1]);
    }

    public function deleteValid(ApiTester $I)
    {
        $I->sendDelete("/instructor/exam-question-sets/3");
        $I->seeResponseCodeIs(HttpCode::NO_CONTENT);
        $I->cantSeeRecord(ExamQuestionSet::class, ['id' => 3]);
    }

    public function listImagesNotFound(ApiTester $I)
    {
        $I->sendGet("/instructor/exam-question-sets/0/images");
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function listImagesWithoutPermission(ApiTester $I)
    {
        $I->sendGet("/instructor/exam-question-sets/4/images");
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function listImages(ApiTester $I)
    {
        $I->sendGet("/instructor/exam-question-sets/1/images");
        $I->seeResponseCodeIs(HttpCode::OK);

        $I->seeResponseContainsJson(
            [
                'name' => 'img1.jpg',
                'url' => '/index-test.php/examination/image/1/img1.jpg',
                'size' => 3884
            ]
        );
        $I->seeResponseContainsJson(
            [
                'name' => 'img2.jpg',
                'url' => '/index-test.php/examination/image/1/img2.jpg',
                'size' => 3884
            ]
        );
        $I->seeResponseMatchesJsonType(self::IMAGE_SCHEMA);
    }

    public function deleteImageSetNotFound(ApiTester $I)
    {
        $I->sendDelete("/instructor/exam-question-sets/0/images/img1.jpg");
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function deleteImageImageNotFound(ApiTester $I)
    {
        $I->sendDelete("/instructor/exam-question-sets/1/images/img0.jpg");
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function deleteImageWithoutPermission(ApiTester $I)
    {
        $I->sendDelete("/instructor/exam-question-sets/4/images/img1.jpg");
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function deleteImage(ApiTester $I)
    {
        $I->sendDelete("/instructor/exam-question-sets/1/images/img1.jpg");
        $I->seeResponseCodeIs(HttpCode::NO_CONTENT);
        $I->cantSeeFileFound(Yii::$app->params['data_dir'] . "/uploadedfiles/examination/1/img1.jpg");
    }

    public function uploadNotFound(ApiTester $I)
    {
        $I->sendPost(
            "/instructor/exam-question-sets/0/images",
            [],
            ['path' => codecept_data_dir("upload_samples/upload_img1.jpg")]
        );
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function uploadWithoutPermission(ApiTester $I)
    {
        $I->sendPost(
            "/instructor/exam-question-sets/4/images",
            [],
            ['path' => codecept_data_dir("upload_samples/upload_img1.jpg")]
        );
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function upload(ApiTester $I)
    {
        $I->sendPost(
            '/instructor/exam-question-sets/1/images',
            [],
            [
                'path' => [
                    codecept_data_dir("upload_samples/upload_img1.jpg"),
                    codecept_data_dir("upload_samples/upload_img2.jpg"),
                ]
            ]
        );
        $I->seeResponseCodeIs(HttpCode::MULTI_STATUS);
        $I->seeResponseMatchesJsonType(self::IMAGE_SCHEMA, '$.[uploaded].[*]');
        $I->seeResponseMatchesJsonType(['uploaded' => 'array', 'failed' => 'array']);
        $data = $I->grabDataFromResponseByJsonPath('$.[uploaded].[*]');
        $I->seeFileFound($data[0]['name'], Yii::$app->params['data_dir'] . '/uploadedfiles/examination/1');
        $I->seeFileFound($data[1]['name'], Yii::$app->params['data_dir'] . '/uploadedfiles/examination/1');
    }
}
