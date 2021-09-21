<?php

namespace tests\api;

use ApiTester;
use Yii;
use app\tests\unit\fixtures\AccessTokenFixture;
use Codeception\Util\HttpCode;

class CommonImagesCest
{
    public function _fixtures()
    {
        return [
            'accesstokens' => [
                'class' => AccessTokenFixture::class,
            ]
        ];
    }

    public function _before(ApiTester $I)
    {
        $I->copyDir(codecept_data_dir("appdata_samples"), Yii::$app->params['data_dir']);
        Yii::$app->language = 'en-US';
    }

    public function _after(ApiTester $I)
    {
        $I->deleteDir(Yii::$app->params['data_dir']);
    }

    public function examImageWithoutToken(ApiTester $I)
    {
        $I->sendGet("/examination/image/1/img1.jpg");
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function examImageExpiredToken(ApiTester $I)
    {
        $I->sendGet("/examination/image/1/img1.jpg", ['imageToken' => 'STUD01;EXPIRED']);
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function examImageValidTokenSetNotFound(ApiTester $I)
    {
        $I->sendGet("/examination/image/0/img1.jpg", ['imageToken' => 'STUD01;VALID']);
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function examImageValidTokenImageNotFound(ApiTester $I)
    {
        $I->sendGet("/examination/image/1/img0.jpg", ['imageToken' => 'STUD01;VALID']);
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function examImageValidTokenImageFound(ApiTester $I)
    {
        $I->sendGet("/examination/image/1/img1.jpg", ['imageToken' => 'STUD01;VALID']);
        $I->seeResponseCodeIs(HttpCode::OK);
    }
}
