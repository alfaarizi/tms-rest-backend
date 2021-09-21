<?php

namespace tests\api;

use ApiTester;
use app\models\ExamQuestion;
use app\tests\unit\fixtures\AccessTokenFixture;
use app\tests\unit\fixtures\AnswerFixture;
use app\tests\unit\fixtures\QuestionFixture;
use app\tests\unit\fixtures\SubmittedAnswerFixture;
use app\tests\unit\fixtures\TestInstanceFixture;
use app\tests\unit\fixtures\TestInstanceQuestionFixture;
use Codeception\Util\HttpCode;

class InstructorExamQuestionsCest
{
    public const QUESTION_SCHEMA = [
        'id' => 'integer',
        'text' => 'string',
        'questionsetID' => 'integer|string'
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
        $I->amBearerAuthenticated("TEACH2;VALID");
    }

    // tests
    public function listForSet(ApiTester $I)
    {
        $I->sendGet("/instructor/exam-questions/list-for-set?questionsetID=1");
        $I->seeResponseCodeIs(HttpCode::OK);

        $I->seeResponseMatchesJsonType(self::QUESTION_SCHEMA, "$.[*]");
        $I->seeResponseContainsJson(
            [
                [
                    'id' => 1,
                    'text' => 'Text',
                    'questionsetID' => 1,
                ],
                [
                    'id' => 2,
                    'text' => 'Hello world',
                    'questionsetID' => 1,
                ],
                [
                    'id' => 3,
                    'text' => 'Hello world',
                    'questionsetID' => 1,
                ]
            ]
        );
    }

    public function listForSetNotFound(ApiTester $I)
    {
        $I->sendGet("/instructor/exam-questions/list-for-set?questionsetID=0");
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function listForSetWithoutPermission(ApiTester $I)
    {
        $I->sendGet("/instructor/exam-questions/list-for-set?questionsetID=4");
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function listForTestNotFound(ApiTester $I)
    {
        $I->sendGet("/instructor/exam-questions/list-for-test?testID=0");
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function listForTestWithoutPermission(ApiTester $I)
    {
        $I->sendGet("/instructor/exam-questions/list-for-test?testID=9");
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function listForTestNotUnique(ApiTester $I)
    {
        $I->sendGet("/instructor/exam-questions/list-for-test?testID=8");
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseContainsJson(
            [
                'id' => 1,
                'text' => 'Text',
                'questionsetID' => 1,
            ]
        );
        $I->seeResponseMatchesJsonType(self::QUESTION_SCHEMA, '$.[*]');
    }

    public function listForTestUniqueMissingUserID(ApiTester $I)
    {
        $I->sendGet("/instructor/exam-questions/list-for-test?testID=1");
        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);
    }

    public function listForTestUnique(ApiTester $I)
    {
        $I->sendGet("/instructor/exam-questions/list-for-test?testID=8&userID=3");
        $I->seeResponseCodeIs(HttpCode::OK);

        $I->seeResponseContainsJson(
            [
                [
                    'id' => 1,
                    'text' => 'Text',
                    'questionsetID' => 1,
                ],
                [
                    'id' => 2,
                    'text' => 'Hello world',
                    'questionsetID' => 1,
                ],
                [
                    'id' => 3,
                    'text' => 'Hello world',
                    'questionsetID' => 1,
                ]
            ]
        );

        $I->cantSeeResponseContainsJson([['id' => 4]]);
        $I->cantSeeResponseContainsJson([['id' => 5]]);
        $I->cantSeeResponseContainsJson([['id' => 6]]);

        $I->seeResponseMatchesJsonType(self::QUESTION_SCHEMA, '$.[*]');
    }

    public function createInvalid(ApiTester $I)
    {
        $I->sendPost(
            '/instructor/exam-questions',
            [
                'questionsetID' => 0
            ]
        );
        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->seeResponseMatchesJsonType(['string'], '$.[*]');
    }

    public function createForbidden(ApiTester $I)
    {
        $I->sendPost(
            '/instructor/exam-questions',
            [
                'questionsetID' => 4,
                'text' => 'test'
            ]
        );
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function createValid(ApiTester $I)
    {
        $I->sendPost(
            '/instructor/exam-questions',
            [
                'questionsetID' => 1,
                'text' => 'Created'
            ]
        );
        $I->seeResponseCodeIs(HttpCode::CREATED);
        $I->seeResponseContainsJson(
            [
                'questionsetID' => 1,
                'text' => 'Created'
            ]
        );
        $I->seeResponseMatchesJsonType(self::QUESTION_SCHEMA);

        $I->seeRecord(
            ExamQuestion::class,
            [
                'questionsetID' => 1,
                'text' => 'Created'
            ]
        );
    }

    public function updateNotFound(ApiTester $I)
    {
        $I->sendPatch(
            '/instructor/exam-questions/0',
            [
                'text' => 'Updated'
            ]
        );
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function updateWithoutPermission(ApiTester $I)
    {
        $I->sendPatch(
            '/instructor/exam-questions/5',
            [
                'text' => 'Updated'
            ]
        );
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);

        $I->seeRecord(
            ExamQuestion::class,
            [
                'id' => 5,
                'text' => 'Question text',
                'questionsetID' => 4,
            ]
        );
    }

    public function updateWithFinalizedSet(ApiTester $I)
    {
        $I->sendPatch(
            '/instructor/exam-questions/1',
            [
                'text' => 'Updated'
            ]
        );
        $I->seeResponseCodeIs(HttpCode::CONFLICT);
        $I->seeRecord(
            ExamQuestion::class,
            [
                'id' => 1,
                'text' => 'Text',
                'questionsetID' => 1,
            ]
        );
    }

    public function updateInvalid(ApiTester $I)
    {
        $I->sendPatch(
            '/instructor/exam-questions/6',
            [
                'text' => ''
            ]
        );
        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->seeResponseMatchesJsonType(['string'], '$.[*]');
        $I->seeRecord(
            ExamQuestion::class,
            [
                'id' => 6,
                'text' => 'Question text',
                'questionsetID' => 3,
            ]
        );
    }

    public function updateValid(ApiTester $I)
    {
        $I->sendPatch(
            '/instructor/exam-questions/6',
            [
                'text' => 'Updated'
            ]
        );
        $I->seeResponseCodeIs(HttpCode::OK);

        $I->seeResponseContainsJson(
            [
                'id' => 6,
                'questionsetID' => 3,
                'text' => 'Updated'
            ]
        );
        $I->seeResponseMatchesJsonType(self::QUESTION_SCHEMA);

        $I->seeRecord(
            ExamQuestion::class,
            [
                'id' => 6,
                'questionsetID' => 3,
                'text' => 'Updated'
            ]
        );
    }

    public function deleteNotFound(ApiTester $I)
    {
        $I->sendDelete('/instructor/exam-questions/0');
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function deleteWithoutPermission(ApiTester $I)
    {
        $I->sendDelete('/instructor/exam-questions/5');
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
        $I->seeRecord(
            ExamQuestion::class,
            [
                'id' => 5,
            ]
        );
    }

    public function deleteWithFinalizedSet(ApiTester $I)
    {
        $I->sendDelete('/instructor/exam-questions/1');
        $I->seeResponseCodeIs(HttpCode::CONFLICT);
        $I->seeRecord(
            ExamQuestion::class,
            [
                'id' => 1,
            ]
        );
    }

    public function delete(ApiTester $I)
    {
        $I->sendDelete('/instructor/exam-questions/6');
        $I->seeResponseCodeIs(HttpCode::NO_CONTENT);


        $I->cantSeeRecord(
            ExamQuestion::class,
            [
                'id' => 6,
            ]
        );
    }
}
