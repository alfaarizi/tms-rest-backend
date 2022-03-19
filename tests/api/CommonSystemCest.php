<?php

namespace tests\api;

use ApiTester;
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

    public function _fixtures(): array
    {
        return [];
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
}
