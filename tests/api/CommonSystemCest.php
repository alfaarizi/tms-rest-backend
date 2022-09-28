<?php

namespace tests\api;

use ApiTester;
use app\tests\unit\fixtures\AccessTokenFixture;
use Codeception\Util\HttpCode;
use Yii;

/**
 * Tests common/SystemController actions
 */
class CommonSystemCest
{
    public const PUBLIC_SETTINGS_SCHEMA = [
        'version' => 'string',
    ];

    public const PRIVATE_SETTINGS_SCHEMA = [
        'uploadMaxFilesize' => 'integer',
        'postMaxSize' => 'integer',
    ];

    public function _fixtures(): array
    {
        return [
            'accesstokens' => [
                'class' => AccessTokenFixture::class,
            ],
        ];
    }

    public function testPublicInfo(ApiTester $I): void
    {
        $I->sendGet('/common/system/public-info');
        $I->seeResponseCodeIs(HttpCode::OK);

        $I->seeResponseMatchesJsonType(self::PUBLIC_SETTINGS_SCHEMA);

        $composerContent = json_decode(
            file_get_contents(Yii::getAlias('@app/composer.json')),
            true
        );
        $expectedVersion = $composerContent['version'];
        $I->seeResponseContainsJson(
            [
                'version' => $expectedVersion
            ]
        );
    }

    public function testPrivateInfo(ApiTester $I): void
    {
        $I->amBearerAuthenticated("TEACH2;VALID");
        $I->sendGet('/common/system/private-info');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseMatchesJsonType(self::PRIVATE_SETTINGS_SCHEMA);
    }
}
