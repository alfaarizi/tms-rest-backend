<?php

namespace tests\api;

use ApiTester;
use app\models\ExamAnswer;
use app\tests\unit\fixtures\AccessTokenFixture;
use app\tests\unit\fixtures\AnswerFixture;
use app\tests\unit\fixtures\QuestionFixture;
use app\tests\unit\fixtures\SubmittedAnswerFixture;
use app\tests\unit\fixtures\TestInstanceFixture;
use app\tests\unit\fixtures\TestInstanceQuestionFixture;
use Codeception\Util\HttpCode;

class InstructorExamAnswersCest
{
    public const ANSWER_SCHEMA =
        [
            'id' => 'integer',
            'text' => 'string',
            'correct' => 'integer|string',
            'questionID' => 'integer|string'
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
    public function indexQuestionNotFound(ApiTester $I)
    {
        $I->sendGet('/instructor/exam-answers?questionID=0');
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function indexQuestionWithoutPermission(ApiTester $I)
    {
        $I->sendGet('/instructor/exam-answers?questionID=5');
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function index(ApiTester $I)
    {
        $I->sendGet('/instructor/exam-answers?questionID=1');
        $I->seeResponseCodeIs(HttpCode::OK);

        $I->seeResponseContainsJson(
            [
                [
                    "id" => 1,
                    "text" => "Answer 1",
                    "correct" => 1,
                    "questionID" => 1,
                ],
                [
                    "id" => 2,
                    "text" => "Answer 2",
                    "correct" => 0,
                    "questionID" => 1,
                ],
                [
                    "id" => 3,
                    "text" => "Answer 3",
                    "correct" => 0,
                    "questionID" => 1,
                ],
                [
                    "id" => 4,
                    "text" => "Answer 4",
                    "correct" => 1,
                    "questionID" => 1,
                ],
                [
                    "id" => 5,
                    "text" => "Answer 5",
                    "correct" => 1,
                    "questionID" => 1,
                ],
            ]
        );

        $I->cantSeeResponseContainsJson([['id' => 6]]);
        $I->cantSeeResponseContainsJson([['id' => 7]]);
        $I->cantSeeResponseContainsJson([['id' => 8]]);
        $I->cantSeeResponseContainsJson([['id' => 9]]);
        $I->cantSeeResponseContainsJson([['id' => 10]]);
        $I->cantSeeResponseContainsJson([['id' => 11]]);
        $I->cantSeeResponseContainsJson([['id' => 12]]);
        $I->cantSeeResponseContainsJson([['id' => 13]]);
        $I->cantSeeResponseContainsJson([['id' => 14]]);

        $I->seeResponseMatchesJsonType(self::ANSWER_SCHEMA, '$.[*]');
    }

    public function createInvalid(ApiTester $I)
    {
        $I->sendPost(
            '/instructor/exam-answers',
            [
                'questionID' => 0
            ]
        );
        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->seeResponseMatchesJsonType(['string'], '$.[*]');
    }

    public function createWithoutPermission(ApiTester $I)
    {
        $I->sendPost(
            '/instructor/exam-answers',
            [
                'questionID' => 5,
                'text' => 'Created',
                'correct' => 1
            ]
        );
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function createForFinalized(ApiTester $I)
    {
        $I->sendPost(
            '/instructor/exam-answers',
            [
                'questionID' => 1,
                'text' => 'Created',
                'correct' => 1
            ]
        );
        $I->seeResponseCodeIs(HttpCode::CONFLICT);
    }

    public function createValid(ApiTester $I)
    {
        $I->sendPost(
            '/instructor/exam-answers',
            [
                'questionID' => 6,
                'text' => 'Created',
                'correct' => 1
            ]
        );
        $I->seeResponseCodeIs(HttpCode::CREATED);
        $I->seeResponseMatchesJsonType(self::ANSWER_SCHEMA);
        $I->seeResponseContainsJson(
            [
                'questionID' => 6,
                'text' => 'Created',
                'correct' => 1
            ]
        );
    }

    public function updateNotFound(ApiTester $I)
    {
        $I->sendPatch('/instructor/exam-answers/0');
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function updateWithoutPermission(ApiTester $I)
    {
        $I->sendPatch(
            '/instructor/exam-answers/14',
            [
                'text' => 'Updated',
                'correct' => 1
            ]
        );
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);

        $I->seeRecord(
            ExamAnswer::class,
            [
                "id" => 14,
                "text" => "Answer 1",
                "correct" => 1,
                "questionID" => 5,
            ]
        );
    }

    public function updateFinalized(ApiTester $I)
    {
        $I->sendPatch(
            '/instructor/exam-answers/1',
            [
                'text' => 'Updated',
                'correct' => 1
            ]
        );
        $I->seeResponseCodeIs(HttpCode::CONFLICT);

        $I->seeRecord(
            ExamAnswer::class,
            [
                "id" => 1,
                "text" => "Answer 1",
                "correct" => 1,
                "questionID" => 1,
            ]
        );
    }

    public function updateInvalid(ApiTester $I)
    {
        $I->sendPatch(
            '/instructor/exam-answers/15',
            [
                'text' => '',
                'correct' => "Correct"
            ]
        );
        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);

        $I->seeRecord(
            ExamAnswer::class,
            [
                "id" => 15,
                "text" => "Answer 1",
                "correct" => 1,
                "questionID" => 6,
            ]
        );
    }

    public function updateValid(ApiTester $I)
    {
        $I->sendPatch(
            '/instructor/exam-answers/15',
            [
                'text' => 'Updated',
            ]
        );
        $I->seeResponseCodeIs(HttpCode::OK);

        $I->seeRecord(
            ExamAnswer::class,
            [
                "id" => 15,
                'text' => 'Updated',
                "correct" => 1,
                "questionID" => 6,
            ]
        );

        $I->seeResponseMatchesJsonType(self::ANSWER_SCHEMA);
        $I->seeResponseContainsJson(
            [
                'id' => 15,
                'text' => 'Updated',
                'correct' => 1,
                "questionID" => 6,
            ]
        );
    }

    public function deleteNotFound(ApiTester $I)
    {
        $I->sendDelete('/instructor/exam-answers/0');
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function deleteWithoutPermission(ApiTester $I)
    {
        $I->sendDelete('/instructor/exam-answers/14');
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);

        $I->seeRecord(
            ExamAnswer::class,
            [
                "id" => 14,
                "text" => "Answer 1",
                "correct" => 1,
                "questionID" => 5,
            ]
        );
    }

    public function deleteFinalized(ApiTester $I)
    {
        $I->sendDelete('/instructor/exam-answers/1');
        $I->seeResponseCodeIs(HttpCode::CONFLICT);

        $I->seeRecord(
            ExamAnswer::class,
            [
                "id" => 1,
                "text" => "Answer 1",
                "correct" => 1,
                "questionID" => 1,
            ]
        );
    }

    public function delete(ApiTester $I)
    {
        $I->sendDelete('/instructor/exam-answers/15');
        $I->seeResponseCodeIs(HttpCode::NO_CONTENT);
        $I->cantSeeRecord(ExamAnswer::class, ['id' => 15]);
    }
}
