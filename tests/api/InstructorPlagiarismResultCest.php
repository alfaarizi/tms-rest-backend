<?php

namespace tests\api;

use ApiTester;
use app\tests\unit\fixtures\PlagiarismFixture;
use Codeception\Util\HttpCode;
use Yii;

/**
 * E2E tests for the PlagiarismResult controller.
 */
class InstructorPlagiarismResultCest
{
    public function _fixtures()
    {
        return [
            'plagiarism' => [
                'class' => PlagiarismFixture::class,
            ],
        ];
    }

    public function _before(ApiTester $I)
    {
        $I->deleteDir(Yii::$app->basePath . '/' . Yii::$app->params['data_dir']);
        $I->copyDir(codecept_data_dir('appdata_samples'), Yii::$app->basePath . '/' . Yii::$app->params['data_dir']);
    }

    public function _after(ApiTester $I)
    {
        $I->deleteDir(Yii::$app->basePath . '/' . Yii::$app->params['data_dir']);
    }

    public function indexNotFound(ApiTester $I)
    {
        $I->sendGet('/instructor/plagiarism-result', ['id' => 0, 'token' => 'ad9e9bcd00632c86b547a1db0f3c9502']);
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function indexNotDownloaded(ApiTester $I)
    {
        $I->sendGet('/instructor/plagiarism-result', ['id' => 6, 'token' => 'ad9e9bcd00632c86b547a1db0f3c9502']);
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function indexWrongToken(ApiTester $I)
    {
        $I->sendGet('/instructor/plagiarism-result', ['id' => 7, 'token' => 'bd9e9bcd00632c86b547a1db0f3c9502']);
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function index(ApiTester $I)
    {
        $I->sendGet('/instructor/plagiarism-result', ['id' => 7, 'token' => 'ad9e9bcd00632c86b547a1db0f3c9502']);
        $I->seeResponseCodeIs(HttpCode::OK);

        $I->openFile(Yii::$app->basePath . '/' . Yii::$app->params['data_dir'] . '/plagiarism/plagiarism-result/7/index.html');
        $I->seeFileContentsEqual($I->grabResponse());
    }

    public function frameInvalid(ApiTester $I)
    {
        $I->sendGet('/instructor/plagiarism-result/frame', ['id' => 7, 'token' => 'ad9e9bcd00632c86b547a1db0f3c9502', 'number' => 0, 'side' => 'bottom']);
        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);
    }

    public function frameNotFound(ApiTester $I)
    {
        $I->sendGet('/instructor/plagiarism-result/frame', ['id' => 0, 'token' => 'ad9e9bcd00632c86b547a1db0f3c9502', 'number' => 0, 'side' => 'top']);
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function frameNotDownloaded(ApiTester $I)
    {
        $I->sendGet('/instructor/plagiarism-result/frame', ['id' => 6, 'token' => 'ad9e9bcd00632c86b547a1db0f3c9502', 'number' => 0, 'side' => 'top']);
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function frameWrongToken(ApiTester $I)
    {
        $I->sendGet('/instructor/plagiarism-result/frame', ['id' => 7, 'token' => 'bd9e9bcd00632c86b547a1db0f3c9502', 'number' => 0, 'side' => 'top']);
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function frameWrongNumber(ApiTester $I)
    {
        $I->sendGet('/instructor/plagiarism-result/frame', ['id' => 7, 'token' => 'ad9e9bcd00632c86b547a1db0f3c9502', 'number' => 1, 'side' => 'top']);
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function frame(ApiTester $I)
    {
        $I->sendGet('/instructor/plagiarism-result/frame', ['id' => 7, 'token' => 'ad9e9bcd00632c86b547a1db0f3c9502', 'number' => 0, 'side' => 'top']);
        $I->seeResponseCodeIs(HttpCode::OK);

        $I->openFile(Yii::$app->basePath . '/' . Yii::$app->params['data_dir'] . '/plagiarism/plagiarism-result/7/match0-top.html');
        $I->seeFileContentsEqual($I->grabResponse());
    }

    public function matchNotFound(ApiTester $I)
    {
        $I->sendGet('/instructor/plagiarism-result/match', ['id' => 0, 'token' => 'ad9e9bcd00632c86b547a1db0f3c9502', 'number' => 0]);
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function matchNotDownloaded(ApiTester $I)
    {
        $I->sendGet('/instructor/plagiarism-result/match', ['id' => 6, 'token' => 'ad9e9bcd00632c86b547a1db0f3c9502', 'number' => 0]);
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function matchWrongToken(ApiTester $I)
    {
        $I->sendGet('/instructor/plagiarism-result/match', ['id' => 7, 'token' => 'bd9e9bcd00632c86b547a1db0f3c9502', 'number' => 0]);
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function match(ApiTester $I)
    {
        $I->sendGet('/instructor/plagiarism-result/match', ['id' => 7, 'token' => 'ad9e9bcd00632c86b547a1db0f3c9502', 'number' => 0]);
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseContains('<title>Plagiarism result</title>');
    }
}
