<?php

namespace app\tests\api;

use ApiTester;
use app\tests\DateFormat;
use DateTime;
use Yii;
use app\models\ExamTest;
use app\models\ExamTestInstance;
use app\tests\unit\fixtures\AccessTokenFixture;
use app\tests\unit\fixtures\AnswerFixture;
use app\tests\unit\fixtures\QuestionFixture;
use app\tests\unit\fixtures\SubmittedAnswerFixture;
use app\tests\unit\fixtures\SubscriptionFixture;
use app\tests\unit\fixtures\TestFixture;
use app\tests\unit\fixtures\TestInstanceFixture;
use app\tests\unit\fixtures\TestInstanceQuestionFixture;
use Codeception\Util\HttpCode;

class InstructorExamTestsCest
{
    public const TEST_SCHEMA = [
        'id' => 'integer',
        'name' => 'string',
        'questionamount' => 'integer',
        'duration' => 'integer',
        'shuffled' => 'integer',
        'unique' => 'integer',
        'availablefrom' => 'string',
        'availableuntil' => 'string',
        'groupID' => 'integer',
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
            "subscriptions" => [
                'class' => SubscriptionFixture::class
            ],
            "tests" => [
                'class' => TestFixture::class
            ]
        ];
    }

    public function _before(ApiTester $I)
    {
        $I->amBearerAuthenticated("TEACH2;VALID");
        Yii::$app->language = "en-US";
    }

    // tests
    public function index(ApiTester $I)
    {
        $I->sendGet('/instructor/exam-tests/index?semesterID=3001');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseMatchesJsonType(self::TEST_SCHEMA, '$.[*]');
        $I->seeResponseContainsJson(
            [
                ['id' => 1],
                ['id' => 2],
                ['id' => 4],
                ['id' => 5],
                ['id' => 6],
                ['id' => 7],
                ['id' => 8],
                ['id' => 10],
                ['id' => 11],
                ['id' => 12],
            ]
        );
        $I->cantSeeResponseContainsJson(
            [
                ['id' => 9]
            ]
        );
    }

    public function viewNotFound(ApiTester $I)
    {
        $I->sendGet('/instructor/exam-tests/0');
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function viewWithoutPermission(ApiTester $I)
    {
        $I->sendGet('/instructor/exam-tests/9');
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function view(ApiTester $I)
    {
        $I->sendGet('/instructor/exam-tests/10');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseMatchesJsonType(self::TEST_SCHEMA);
        $I->seeResponseContainsJson(
            [
                'id' => 10,
                'name' => 'Vizsga',
                'questionamount' => 1,
                'duration' => 30,
                'shuffled' => 1,
                'unique' => 1,
                'availablefrom' => "2021-02-01T10:00:00+01:00",
                'availableuntil' => "2021-02-01T11:00:00+01:00",
                'groupID' => 2000,
                'questionsetID' => 1,
            ]
        );
    }

    public function createInvalid(ApiTester $I)
    {
        $I->sendPost(
            '/instructor/exam-tests',
            [
                'name' => 'Created',
            ]
        );
        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->seeResponseMatchesJsonType(['string'], '$.[*]');
        $I->cantSeeRecord(ExamTest::class, ['name' => 'Created']);
    }

    public function createWithoutPermissionForCourse(ApiTester $I)
    {
        $I->sendPost(
            '/instructor/exam-tests',
            [
                'name' => 'Created',
                'questionamount' => 1,
                'duration' => 90,
                'shuffled' => 0,
                'unique' => 1,
                'availablefrom' => date('Y-m-d H:i:s'),
                'availableuntil' => date('Y-m-d H:i:s', strtotime('+1 day')),
                'questionsetID' => 4,
                'groupID' => 2001,
            ]
        );
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
        $I->cantSeeRecord(ExamTest::class, ['name' => 'Created']);
    }

    public function createWithoutPermissionForGroup(ApiTester $I)
    {
        $I->sendPost(
            '/instructor/exam-tests',
            [
                'name' => 'Created',
                'questionamount' => 1,
                'duration' => 90,
                'shuffled' => 0,
                'unique' => 1,
                'availablefrom' => date('Y-m-d H:i:s'),
                'availableuntil' => date('Y-m-d H:i:s', strtotime('+1 day')),
                'questionsetID' => 1,
                'groupID' => 2002,
            ]
        );
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
        $I->cantSeeRecord(ExamTest::class, ['name' => 'Created']);
    }

    public function createValid(ApiTester $I)
    {
        $from = new \DateTime();
        $until = new \DateTime('+1 day');
        $I->sendPost(
            '/instructor/exam-tests',
            [
                'name' => 'Created',
                'questionamount' => 1,
                'duration' => 90,
                'shuffled' => 0,
                'unique' => 1,
                'availablefrom' => $from->format(\DateTime::ATOM),
                'availableuntil' => $until->format(\DateTime::ATOM),
                'questionsetID' => 1,
                'groupID' => 2000,
            ]
        );
        $I->seeResponseCodeIs(HttpCode::CREATED);

        $I->seeResponseContainsJson(
            [
                'name' => 'Created',
                'questionamount' => 1,
                'duration' => 90,
                'shuffled' => 0,
                'unique' => 1,
                'availablefrom' => $from->format(\DateTime::ATOM),
                'availableuntil' => $until->format(\DateTime::ATOM),
                'questionsetID' => 1,
                'groupID' => 2000,
            ]
        );

        $I->seeRecord(
            ExamTest::class,
            [
                'name' => 'Created',
                'questionamount' => 1,
                'duration' => 90,
                'shuffled' => 0,
                'unique' => 1,
                'availablefrom' => $from->format(DateFormat::MYSQL),
                'availableuntil' => $until->format(DateFormat::MYSQL),
                'questionsetID' => 1,
                'groupID' => 2000,
            ]
        );
    }


    public function updateInvalid(ApiTester $I)
    {
        $I->sendPatch(
            '/instructor/exam-tests/10',
            [
                'name' => 'Updated',
                'questionamount' => 'one',
            ]
        );
        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->seeResponseMatchesJsonType(['string'], '$.[*]');
        $I->cantSeeRecord(ExamTest::class, ['name' => '']);
    }

    public function updateWithoutPermission(ApiTester $I)
    {
        $I->sendPatch(
            '/instructor/exam-tests/9',
            [
                'name' => 'Updated',
                'questionamount' => 1,
            ]
        );
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
        $I->cantSeeRecord(ExamTest::class, ['name' => 'Updated']);
    }

    public function updateFinalized(ApiTester $I)
    {
        $I->sendPatch(
            '/instructor/exam-tests/1',
            [
                'name' => 'Updated'
            ]
        );
        $I->seeResponseCodeIs(HttpCode::CONFLICT);
        $I->cantSeeRecord(ExamTest::class, ['name' => 'Updated']);
    }

    public function updateNotFound(ApiTester $I)
    {
        $I->sendPatch(
            '/instructor/exam-tests/0',
            [
                'name' => 'Updated'
            ]
        );
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }


    public function updateValid(ApiTester $I)
    {
        $from = new \DateTime();
        $until = new \DateTime('+1 day');
        $I->sendPatch(
            '/instructor/exam-tests/10',
            [
                'name' => 'Updated',
                'availablefrom' => $from->format(\DateTime::ATOM),
                'availableuntil' => $until->format(\DateTime::ATOM),
            ]
        );
        $I->seeResponseCodeIs(HttpCode::OK);

        $I->seeResponseContainsJson(
            [
                'name' => 'Updated',
                'questionamount' => 1,
                'duration' => 30,
                'shuffled' => 1,
                'unique' => 1,
                'availablefrom' => $from->format(\DateTime::ATOM),
                'availableuntil' => $until->format(\DateTime::ATOM),
                'questionsetID' => 1,
                'groupID' => 2000,
            ]
        );

        $I->seeRecord(
            ExamTest::class,
            [
                'name' => 'Updated',
                'availablefrom' => $from->format(DateFormat::MYSQL),
                'availableuntil' => $until->format(DateFormat::MYSQL)
            ]
        );
    }


    public function deleteWithoutPermission(ApiTester $I)
    {
        $I->sendDelete('/instructor/exam-tests/9');
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
        $I->seeRecord(ExamTest::class, ['id' => 9]);
    }

    public function deleteFinalized(ApiTester $I)
    {
        $I->sendDelete('/instructor/exam-tests/1');
        $I->seeResponseCodeIs(HttpCode::CONFLICT);
        $I->seeRecord(ExamTest::class, ['id' => 1]);
    }

    public function deleteNotFound(ApiTester $I)
    {
        $I->sendDelete('/instructor/exam-tests/0');
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function delete(ApiTester $I)
    {
        $I->sendDelete('/instructor/exam-tests/11');
        $I->seeResponseCodeIs(HttpCode::NO_CONTENT);
        $I->cantSeeRecord(ExamTest::class, ['id' => 11]);
    }

    public function duplicateNotFound(ApiTester $I)
    {
        $I->sendPost("/instructor/exam-tests/0/duplicate");
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function duplicateWithoutPermission(ApiTester $I)
    {
        $I->sendPost("/instructor/exam-tests/9/duplicate");
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function duplicateAvailable(ApiTester $I)
    {
        $I->sendPost("/instructor/exam-tests/1/duplicate");
        $I->seeResponseCodeIs(HttpCode::CREATED);
        $I->seeResponseMatchesJsonType(self::TEST_SCHEMA);

        $test = $I->grabRecord(ExamTest::class, ['id' => 1]);

        $I->seeResponseContainsJson(
            [
                'name' => $test->name . ' ' . '(copy)',
                'questionamount' => 1,
                'duration' => 110,
                'shuffled' => 1,
                'unique' => 1,
                'availablefrom' => $test->availablefrom,
                'availableuntil' => $test->availableuntil,
                'questionsetID' => 1,
                'groupID' => 2000,
            ]
        );

        $I->seeRecord(
            ExamTest::class,
            [
                'name' => $test->name . ' ' . '(copy)',
                'questionamount' => 1,
                'duration' => 110,
                'shuffled' => 1,
                'unique' => 1,
                'availablefrom' => \DateTime::createFromFormat(\DateTime::ATOM, $test->availablefrom)
                    ->format(DateFormat::MYSQL),
                'availableuntil' => \DateTime::createFromFormat(\DateTime::ATOM, $test->availableuntil)
                    ->format(DateFormat::MYSQL),
                'questionsetID' => 1,
                'groupID' => 2000,
            ]
        );
    }

    public function finalizeNotFound(ApiTester $I)
    {
        $I->sendPost("/instructor/exam-tests/0/finalize");
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function finalizeWithoutPermission(ApiTester $I)
    {
        $I->sendPost("/instructor/exam-tests/9/finalize");
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function finalizeWithoutEnoughQuestions(ApiTester $I)
    {
        $I->sendPost("/instructor/exam-tests/11/finalize");
        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);
        $I->seeResponseContainsJson(
            [
                'message' => 'This question set doesn\'t have enough questions'
            ]
        );
    }

    public function finalizeEmptyGroup(ApiTester $I)
    {
        $I->sendPost("/instructor/exam-tests/12/finalize");
        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);
        $I->seeResponseContainsJson(
            [
                'message' => 'The selected group is empty. Please add at least one student!'
            ]
        );
    }

    public function finalize(ApiTester $I)
    {
        $I->sendPost("/instructor/exam-tests/10/finalize");
        $I->seeResponseCodeIs(HttpCode::NO_CONTENT);

        $I->seeRecord(
            ExamTestInstance::class,
            [
                'testID' => 10,
                'userID' => 1001
            ]
        );
        $I->seeRecord(
            ExamTestInstance::class,
            [
                'testID' => 10,
                'userID' => 1002
            ]
        );
        $I->seeRecord(
            ExamTestInstance::class,
            [
                'testID' => 10,
                'userID' => 1003
            ]
        );
    }
}
