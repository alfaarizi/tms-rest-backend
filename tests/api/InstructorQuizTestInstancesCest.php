<?php

namespace app\tests\api;

use ApiTester;
use Yii;
use app\tests\unit\fixtures\AccessTokenFixture;
use app\tests\unit\fixtures\AnswerFixture;
use app\tests\unit\fixtures\QuestionFixture;
use app\tests\unit\fixtures\SubmittedAnswerFixture;
use app\tests\unit\fixtures\TestInstanceFixture;
use app\tests\unit\fixtures\TestInstanceQuestionFixture;
use Codeception\Util\HttpCode;

class InstructorQuizTestInstancesCest
{
    public const TEST_INSTANCE_SCHEMA = [
        'id' => 'integer',
        'score' => 'integer',
        'user' => [
            'id' => 'integer',
            'userCode' => 'string',
            'name' => 'string'
        ],
        'testDuration' => 'integer',
        'isUnlocked' => 'boolean'
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
    public function indexTestNotFound(ApiTester $I)
    {
        $I->sendGet('/instructor/quiz-test-instances?testID=0');
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function indexWithoutPermission(ApiTester $I)
    {
        $I->sendGet('/instructor/quiz-test-instances?testID=9');
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function indexSubmittedIsNull(ApiTester $I)
    {
        $I->sendGet('/instructor/quiz-test-instances?testID=1');
        $I->seeResponseCodeIs(HttpCode::OK);

        $I->seeResponseContainsJson(
            [
                ['id' => 1],
                ['id' => 2],
                ['id' => 3],
                ['id' => 4],
                ['id' => 5],
                ['id' => 9]
            ]
        );
        $I->seeResponseMatchesJsonType(self::TEST_INSTANCE_SCHEMA, '$.[*]');
    }

    public function indexSubmittedIsFalse(ApiTester $I)
    {
        $I->sendGet('/instructor/quiz-test-instances?testID=1&submitted=false');
        $I->seeResponseCodeIs(HttpCode::OK);

        $I->seeResponseContainsJson(
            [
                ['id' => 2],
                ['id' => 3],
                ['id' => 4],
                ['id' => 9],
            ]
        );
        $I->cantSeeResponseContainsJson([['id' => 1]]);
        $I->seeResponseMatchesJsonType(self::TEST_INSTANCE_SCHEMA, '$.[*]');
    }

    public function indexSubmittedIsTrue(ApiTester $I)
    {
        $I->sendGet('/instructor/quiz-test-instances?testID=1&submitted=true');
        $I->seeResponseCodeIs(HttpCode::OK);

        $I->seeResponseContainsJson(
            [
                ['id' => 1]
            ]
        );

        $I->cantSeeResponseContainsJson([['id' => 2]]);
        $I->cantSeeResponseContainsJson([['id' => 3]]);
        $I->cantSeeResponseContainsJson([['id' => 4]]);
        $I->cantSeeResponseContainsJson([['id' => 9]]);

        $I->seeResponseMatchesJsonType(self::TEST_INSTANCE_SCHEMA, '$.[*]');
    }
}
