<?php

namespace app\tests\api;

use ApiTester;
use app\tests\unit\fixtures\AccessTokenFixture;
use app\tests\unit\fixtures\AdminIpRestrictionFixture;
use Codeception\Util\HttpCode;

class AdminIpRestrictionCest
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
        $I->amBearerAuthenticated("BATMAN;12345");
    }

    public function index(ApiTester $I)
    {
        $I->sendGet('/admin/ip-restriction');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();
        $I->seeResponseMatchesJsonType(self::IP_RESTRICTION_SCHEMA, '$.[*]');
        $I->seeResponseContainsJson([
            ['id' => 1, 'name' => 'Local Network', 'ipAddress' => '192.168.1.1', 'ipMask' => '255.255.255.0'],
            ['id' => 2, 'name' => 'Remote Access', 'ipAddress' => '192.168.2.1', 'ipMask' => '255.255.255.0']
        ]);
    }

    public function view(ApiTester $I)
    {
        $I->sendGet('/admin/ip-restriction/1');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseContainsJson(['id' => 1, 'name' => 'Local Network']);
        $I->seeResponseMatchesJsonType(self::IP_RESTRICTION_SCHEMA);
    }

    public function create(ApiTester $I)
    {
        $I->sendPost('/admin/ip-restriction', [
            'name' => 'New Network',
            'ipAddress' => '192.168.1.1',
            'ipMask' => '255.255.255.0'
        ]);

        $I->seeResponseCodeIs(HttpCode::CREATED);
        $I->seeResponseContainsJson(['name' => 'New Network']);
        $I->seeRecord('app\models\IpRestriction', ['name' => 'New Network']);
    }

    public function createInvalid(ApiTester $I)
    {
        $I->sendPost('/admin/ip-restriction', [
            'name' => 'New Network'
        ]);
        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->cantSeeRecord('app\models\IpRestriction', ['name' => 'New Network']);
    }

    public function update(ApiTester $I)
    {
        $I->sendPatch('/admin/ip-restriction/1', [
            'name' => 'New Network',
            'ipAddress' => '192.168.1.1',
            'ipMask' => '255.255.255.0'
        ]);
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseContainsJson(['id' => 1, 'name' => 'New Network', 'ipAddress' => '192.168.1.1', 'ipMask' => '255.255.255.0']);
        $I->seeRecord('app\models\IpRestriction', ['name' => 'New Network']);
    }

    public function updateInvalid(ApiTester $I)
    {
        $I->sendPatch('/admin/ip-restriction/1', []);
        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->cantSeeRecord('app\models\IpRestriction', ['name' => 'UpdateInvalid']);
    }

    public function updateNotFound(ApiTester $I)
    {
        $I->sendPatch('/admin/ip-restriction/999', [
            'name' => 'Local Network',
            'ipAddress' => '192.168.1.1',
            'ipMask' => '255.255.0.0',
        ]);
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function delete(ApiTester $I)
    {
        $I->sendDelete('/admin/ip-restriction/1');
        $I->seeResponseCodeIs(HttpCode::NO_CONTENT);
    }

    public function deleteNotFound(ApiTester $I)
    {
        $I->sendDelete('/admin/ip-restriction/999');
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function viewNotFound(ApiTester $I)
    {
        $I->sendGet('/admin/ip-restriction/999');
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }
}
