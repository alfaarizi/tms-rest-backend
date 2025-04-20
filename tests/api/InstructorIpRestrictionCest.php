<?php

namespace app\tests\api;

use ApiTester;
use app\tests\unit\fixtures\AccessTokenFixture;
use app\tests\unit\fixtures\AdminIpRestrictionFixture;
use Codeception\Util\HttpCode;

class InstructorIpRestrictionCest
{
    public const IP_RESTRICTION_SCHEMA = [
        'id' => 'integer',
        'name' => 'string',
        'ipAddress' => 'string',
        'ipMask' => 'string',
    ];

    public function _fixtures()
    {
        return [
            'accesstokens' => [
                'class' => AccessTokenFixture::class,
            ],
            'ipRestrictions' => [
                'class' => AdminIpRestrictionFixture::class,
            ],
        ];
    }

    public function _before(ApiTester $I)
    {
        $I->amBearerAuthenticated("TEACH2;VALID");
        $I->haveHttpHeader('Content-Type', 'application/json');
    }

    public function index(ApiTester $I)
    {
        $I->sendGet('/instructor/ip-restriction');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();
        $I->seeResponseMatchesJsonType(self::IP_RESTRICTION_SCHEMA, '$.[*]');
        $I->seeResponseContainsJson([
            ['id' => 1, 'name' => 'Local Network', 'ipAddress' => '192.168.1.1', 'ipMask' => '255.255.255.0'],
            ['id' => 2, 'name' => 'Remote Access', 'ipAddress' => '192.168.2.1', 'ipMask' => '255.255.255.0']
        ]);
    }
}
