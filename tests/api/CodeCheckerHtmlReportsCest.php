<?php

namespace tests\api;

use ApiTester;
use app\tests\unit\fixtures\CodeCheckerResultFixture;
use Codeception\Util\HttpCode;
use Yii;

class CodeCheckerHtmlReportsCest
{
    public function _fixtures(): array
    {
        return [
            'codecheckerresults' => [
                'class' => CodeCheckerResultFixture::class
            ],
        ];
    }

    public function _before(ApiTester $I)
    {
        $I->copyDir(codecept_data_dir("appdata_samples"), Yii::getAlias("@appdata"));
        Yii::$app->language = 'en-US';
    }

    public function _after(ApiTester $I)
    {
        $I->deleteDir(Yii::getAlias("@appdata"));
    }

    public function view(ApiTester $I)
    {
        $I->sendGet('common/code-checker-html-reports/1/token/index.html');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseContains('<title>Plist HTML Viewer</title>');
    }

    public function viewWithIncorrectToken(ApiTester $I)
    {
        $I->sendGet('common/code-checker-html-reports/1/token-wrong/index.html');
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function resultNotFound(ApiTester $I)
    {
        $I->sendGet('common/code-checker-html-reports/0/token/index.html');
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function fileNotFound(ApiTester $I)
    {
        $I->sendGet('common/code-checker-html-reports/1/token/not_found.html');
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }
}
