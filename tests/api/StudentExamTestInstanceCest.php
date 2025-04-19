<?php

namespace app\tests\api;

use ApiTester;
use app\models\QuizSubmittedAnswer;
use app\models\QuizTestInstance;
use app\tests\unit\fixtures\AccessTokenFixture;
use app\tests\unit\fixtures\AnswerFixture;
use app\tests\unit\fixtures\QuestionFixture;
use app\tests\unit\fixtures\SubmittedAnswerFixture;
use app\tests\unit\fixtures\TestInstanceFixture;
use app\tests\unit\fixtures\TestInstanceQuestionFixture;
use Codeception\Util\HttpCode;

class StudentQuizTestInstanceCest
{
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
        $I->amBearerAuthenticated("STUD02;VALID");
    }


    public function indexSubmitted(ApiTester $I)
    {
        $I->sendGet("/student/quiz-test-instances?semesterID=3001&submitted=true&future=false");
        $I->seeResponseCodeIs(HttpCode::OK); // 200

        $I->seeResponseIsJson();

        $I->seeResponseContainsJson(
            [
                ['id' => 1],
                ['id' => 5],
            ]
        );

        $I->cantSeeResponseContainsJson(
            [
                ['id' => 2],
                ['id' => 3],
                ['id' => 4],
                ['id' => 6],
                ['id' => 7],
                ['id' => 8],
                ['id' => 9],
                ['id' => 10],
                ['id' => 11]
            ]
        );

        $I->seeResponseMatchesJsonType(
            [
                'id' => 'integer',
                'submitted' => 'integer',
                'starttime' => 'string',
                'finishtime' => 'string',
                'score' => 'integer',
                'maxScore' => 'integer',
                'test' =>
                    [
                        'name' => 'string',
                        'duration' => 'integer',
                        'availablefrom' => 'string',
                        'availableuntil' => 'string',
                        'groupID' => 'integer',
                    ],
            ],
            '$.[*]'
        );
    }
    public function indexNotSubmitted(ApiTester $I)
    {
        $I->sendGet("/student/quiz-test-instances?semesterID=3001&submitted=false&future=false");
        $I->seeResponseCodeIs(HttpCode::OK); // 200

        $I->seeResponseIsJson();

        $I->seeResponseContainsJson(
            [
                ['id' => 3],
                ['id' => 4],
                ['id' => 8],
                ['id' => 9],
            ]
        );

        $I->cantSeeResponseContainsJson([['id' => 1]]);
        $I->cantSeeResponseContainsJson([['id' => 2]]);
        $I->cantSeeResponseContainsJson([['id' => 5]]);
        $I->cantSeeResponseContainsJson([['id' => 6]]);
        $I->cantSeeResponseContainsJson([['id' => 7]]);
        $I->cantSeeResponseContainsJson([['id' => 10]]);

        $I->seeResponseMatchesJsonType(
            [
                'id' => 'integer',
                'submitted' => 'integer',
                'starttime' => 'string|null',
                'finishtime' => 'null',
                'score' => 'integer',
                'maxScore' => 'integer',
                'test' =>
                    [
                        'name' => 'string',
                        'duration' => 'integer',
                        'availablefrom' => 'string',
                        'availableuntil' => 'string',
                        'groupID' => 'integer',
                    ],
            ],
            '$.[*]'
        );
    }

    public function indexFuture(ApiTester $I)
    {
        $I->sendGet("/student/quiz-test-instances?semesterID=3001&submitted=false&future=true");
        $I->seeResponseCodeIs(HttpCode::OK); // 200

        $I->seeResponseIsJson();

        $I->seeResponseContainsJson(
            [
                ['id' => 10],
            ]
        );

        $I->cantSeeResponseContainsJson(
            [
                ['id' => 1],
                ['id' => 2],
                ['id' => 3],
                ['id' => 4],
                ['id' => 5],
                ['id' => 6],
                ['id' => 7],
                ['id' => 8],
                ['id' => 9],
            ]
        );

        $I->seeResponseMatchesJsonType(
            [
                'id' => 'integer',
                'submitted' => 'integer',
                'starttime' => 'string',
                'finishtime' => 'null',
                'score' => 'integer',
                'maxScore' => 'integer',
                'test' =>
                    [
                        'name' => 'string',
                        'duration' => 'integer',
                        'availablefrom' => 'string',
                        'availableuntil' => 'string',
                        'groupID' => 'integer',
                    ],
            ],
            '$.[*]'
        );
    }

    public function viewNotFound(ApiTester $I)
    {
        $I->sendGet("/student/quiz-test-instances/0");
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function viewAnotherUsersInstance(ApiTester $I)
    {
        $I->sendGet("/student/quiz-test-instances/2");
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function viewNotAvailableNotSubmitted(ApiTester $I)
    {
        $I->sendGet("/student/quiz-test-instances/7");
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function viewNotAvailableSubmitted(ApiTester $I)
    {
        $I->sendGet("/student/quiz-test-instances/6");
        $I->seeResponseCodeIs(HttpCode::OK);
    }

    public function view(ApiTester $I)
    {
        $I->sendGet("/student/quiz-test-instances/1");
        $I->seeResponseCodeIs(HttpCode::OK); // 200

        $I->seeResponseIsJson();

        $I->seeResponseContainsJson(
            [
                'id' => 1,
                'test' => ['name' => 'Vizsga']
            ]
        );

        $I->seeResponseMatchesJsonType(
            [
                'id' => 'integer',
                'submitted' => 'integer',
                'starttime' => 'string',
                'finishtime' => 'string',
                'score' => 'integer',
                'maxScore' => 'integer',
                'test' =>
                    [
                        'name' => 'string',
                        'duration' => 'integer',
                        'availablefrom' => 'string',
                        'availableuntil' => 'string',
                        'groupID' => 'integer',
                    ],
            ]
        );
    }

    public function resultNotFound(ApiTester $I)
    {
        $I->sendGet("/student/quiz-test-instances/0/results");
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function resultAnotherUsersInstance(ApiTester $I)
    {
        $I->sendGet("/student/quiz-test-instances/2/results");
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function resultNotSubmitted(ApiTester $I)
    {
        $I->sendGet("/student/quiz-test-instances/3/results");
        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);
    }

    public function results(ApiTester $I)
    {
        $I->sendGet("/student/quiz-test-instances/1/results");
        $I->seeResponseCodeIs(HttpCode::OK);

        $I->seeResponseMatchesJsonType(
            [
                'questionID' => 'integer',
                'questionText' => 'string',
                'isCorrect' => 'boolean',
                'answerText' => "string"
            ],
            "$.[*]"
        );
    }

    public function startWriteNotFound(ApiTester $I)
    {
        $I->sendPost("/student/quiz-test-instances/0/start-write");
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function startWriteAnotherUsersInstance(ApiTester $I)
    {
        $I->sendPost("/student/quiz-test-instances/2/start-write");
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function startWriteAlreadySubmitted(ApiTester $I)
    {
        $I->sendPost("/student/quiz-test-instances/6/start-write");
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function startWriteNotAvailable(ApiTester $I)
    {
        $I->sendPost("/student/quiz-test-instances/7/start-write");
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }


    public function startWriteNotAvailableNotFinished(ApiTester $I)
    {
        $I->sendPost("/student/quiz-test-instances/8/start-write");
        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);
    }


    public function startWriteValid(ApiTester $I)
    {
        $I->seeRecord(\app\models\QuizTestInstance::class, ['id' => 3, 'starttime' => null]);
        $I->sendPost("/student/quiz-test-instances/3/start-write");
        $I->seeResponseCodeIs(HttpCode::OK);

        $I->seeResponseMatchesJsonType(
            [
                'testName' => 'string',
                'duration' => 'integer',
                'questions' => 'array'
            ]
        );

        $I->seeResponseMatchesJsonType(
            [
                'questionID' => 'integer',
                'text' => 'string',
                'answers' => 'array'
            ],
            "$.questions[*]"
        );

        $I->seeResponseMatchesJsonType(
            [
                'id' => 'integer',
                'text' => 'string'
            ],
            "$.questions[*].answers[*]"
        );

        $I->seeResponseContainsJson(
            [
                "testName" => "Vizsga",
                //"duration"  => 6600,
                "questions" => [
                    [
                        "questionID" => 1,
                        "text" => "Text",
                        "answers" => [
                            [
                                "id" => 1,
                                "text" => "Answer 1"
                            ],
                            [
                                "id" => 2,
                                "text" => "Answer 2"
                            ],
                            [
                                "id" => 3,
                                "text" => "Answer 3"
                            ],
                            [
                                "id" => 4,
                                "text" => "Answer 4"
                            ],
                            [
                                "id" => 5,
                                "text" => "Answer 5"
                            ]
                        ]
                    ]
                ]
            ]
        );
    }

    public function startNotVerified(ApiTester $I)
    {
        $I->seeRecord(\app\models\QuizTestInstance::class, ['id' => 11, 'starttime' => null]);
        $I->sendPost("/student/quiz-test-instances/11/start-write");
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function startVerified(ApiTester $I)
    {
        $I->seeRecord(\app\models\QuizTestInstance::class, ['id' => 12, 'starttime' => null]);
        $I->sendPost("/student/quiz-test-instances/12/start-write");
        $I->seeResponseCodeIs(HttpCode::OK);
    }

    public function finishWriteNotFound(ApiTester $I)
    {
        $I->sendPost("/student/quiz-test-instances/0/finish-write");
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function finishWriteAnotherUsersInstance(ApiTester $I)
    {
        $I->sendPost("/student/quiz-test-instances/2/finish-write");
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function finishWriteAlreadySubmitted(ApiTester $I)
    {
        $I->sendPost("/student/quiz-test-instances/6/finish-write");
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function finishWriteNotAvailable(ApiTester $I)
    {
        $I->sendPost("/student/quiz-test-instances/7/finish-write");
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }


    public function finishWriteTimeExpired(ApiTester $I)
    {
        $I->sendPost(
            "/student/quiz-test-instances/8/finish-write",
            [
                ['answerID' => 1]
            ]
        );
        $I->seeResponseCodeIs(HttpCode::OK);


        // Test saved with 0 points
        $I->seeRecord(
            \app\models\QuizTestInstance::class,
            [
                'id' => 8,
                'submitted' => true,
                'score' => 0,
                // 'finishtime' => date("Y-m-d H:i:s")
            ]
        );

        // Answers are saved
        $I->seeRecord(\app\models\QuizSubmittedAnswer::class, ['testinstanceID' => 8, 'answerID' => null]);

        $I->seeResponseContainsJson(
            [
                'id' => 8,
                'test' => ['name' => 'Vizsga']
            ]
        );

        $I->seeResponseMatchesJsonType(
            [
                'id' => 'integer',
                'submitted' => 'boolean',
                'starttime' => 'string',
                'finishtime' => 'string',
                'score' => 'integer',
                'maxScore' => 'integer',
                'test' =>
                    [
                        'name' => 'string',
                        'duration' => 'integer',
                        'availablefrom' => 'string',
                        'availableuntil' => 'string',
                        'groupID' => 'integer',
                    ],
            ]
        );
    }

    public function finishWriteTimeValid(ApiTester $I)
    {
        $I->seeRecord(
            \app\models\QuizTestInstance::class,
            [
                'id' => 9,
                'submitted' => false,
                'score' => 0,
                'finishtime' => null
            ]
        );

        $I->sendPost(
            "/student/quiz-test-instances/9/finish-write",
            [
                ['answerID' => 1],
                ['answerID' => 12],
            ]
        );
        $I->seeResponseCodeIs(HttpCode::OK);


        $I->seeRecord(
            \app\models\QuizTestInstance::class,
            [
                'id' => 9,
                'submitted' => true,
                'score' => 1,
                //'finishtime' => date("Y-m-d H:i:s")
            ]
        );

        // Answers are saved
        $I->seeRecord(\app\models\QuizSubmittedAnswer::class, ['testinstanceID' => 9, "answerID" => 1]);
        $I->seeRecord(\app\models\QuizSubmittedAnswer::class, ['testinstanceID' => 9, "answerID" => 12]);
        $I->seeRecord(\app\models\QuizSubmittedAnswer::class, ['testinstanceID' => 9, "answerID" => null]);


        $I->seeResponseContainsJson(
            [
                'id' => 9,
                'test' => ['name' => 'Vizsga']
            ]
        );

        $I->seeResponseMatchesJsonType(
            [
                'id' => 'integer',
                'submitted' => 'boolean',
                'starttime' => 'string',
                'finishtime' => 'string',
                'score' => 'integer',
                'maxScore' => 'integer',
                'test' =>
                    [
                        'name' => 'string',
                        'duration' => 'integer',
                        'availablefrom' => 'string',
                        'availableuntil' => 'string',
                        'groupID' => 'integer',
                    ],
            ]
        );
    }


    public function finishSameAnswerID(ApiTester $I)
    {
        $I->sendPost(
            "/student/quiz-test-instances/9/finish-write",
            [
                ['answerID' => 1],
                ['answerID' => 1],
            ]
        );
        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->seeResponseContainsJson(
            [
                "answerID" => ["The combination \"9\"-\"1\" of Test Instance ID and Answer ID has already been taken."]
            ]
        );
    }

    public function finishSameQuestionID(ApiTester $I)
    {
        $I->sendPost(
            "/student/quiz-test-instances/9/finish-write",
            [
                ['answerID' => 1],
                ['answerID' => 2],
                ['answerID' => 11],
                ['answerID' => 13],
            ]
        );
        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->seeResponseContainsJson(
            [
                "answerID" => ["Answer is already saved for this question"]
            ]
        );
    }

    public function finishNotVerified(ApiTester $I)
    {
        $I->sendPost(
            "/student/quiz-test-instances/13/finish-write",
            [
                ['answerID' => 1],
                ['answerID' => 12],
            ]
        );
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function finishVerified(ApiTester $I)
    {
        $I->sendPost(
            "/student/quiz-test-instances/12/finish-write",
            [
                ['answerID' => 1],
                ['answerID' => 12],
            ]
        );
        $I->seeResponseCodeIs(HttpCode::OK);
    }


    public function unlockExamAlreadyUnlocked(ApiTester $I)
    {
        $I->sendPost(
            '/student/quiz-test-instances/unlock',
            [
                'id' => 12,
                'password' => 'password',
            ]
        );
        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);
    }

    public function unlockExamInvalidRequest(ApiTester $I)
    {
        $I->sendPost(
            '/student/quiz-test-instances/unlock',
            [
                'password' => 'password'
            ]
        );
        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
    }

    public function unlockExamWrongPassword(ApiTester $I)
    {
        $I->sendPost(
            '/student/quiz-test-instances/unlock',
            [
                'id' => 11,
                'password' => 'wrong',
            ]
        );
        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
    }

    public function unlockWithoutPermission(ApiTester $I)
    {
        $I->sendPost(
            '/student/quiz-test-instances/unlock',
            [
                'id' => 14,
                'password' => 'password',
            ]
        );
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function unlockSolution(ApiTester $I)
    {
        $I->sendPost(
            '/student/quiz-test-instances/unlock',
            [
                'id' => 11,
                'password' => 'password',
            ]
        );
        $I->seeResponseCodeIs(HttpCode::OK);

        $I->seeResponseContainsJson(
            [
                'id' => 11,
                'submitted' => 0,
                'starttime' => null,
                'finishtime' => null,
                'score' => 0,
                'isUnlocked' => true
            ]
        );
        $I->seeRecord(
            QuizTestInstance::class,
            [
                "id" => 11,
                "token" => "STUD02;VALID"
            ]
        );
    }
}
