<?php

namespace app\tests\unit\helpers;

use app\modules\student\resources\TaskResource;
use app\modules\student\helpers\PermissionHelpers;
use Yii;
use yii\web\ForbiddenHttpException;
use yii\web\Request;
use yii\db\ActiveQuery;
use Codeception\Test\Unit;

class IpRestrictionTest extends Unit
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->mockWebRequest();
    }

    protected function mockWebRequest()
    {
        Yii::$app->set('request', $this->createMock(Request::class));
    }

    public function testCheckIfIpAddressAllowedValidIp()
    {
        Yii::$app->request->method('getUserIP')->willReturn('192.168.1.1');
        Yii::$app->request->method('__get')->with('userIP')->willReturn('192.168.1.1');

        $query = $this->createMock(ActiveQuery::class);
        $query->method('all')->willReturn([
            (object)[
                'ipAddress' => '192.168.1.0',
                'ipMask' => '255.255.255.0',
            ]
        ]);

        $task = $this->getMockBuilder(TaskResource::class)
            ->onlyMethods(['getIpRestrictions'])
            ->getMock();
        $task->method('getIpRestrictions')->willReturn($query);

        PermissionHelpers::checkIfTaskIpAddressAllowed($task);
        $this->expectNotToPerformAssertions();
    }

    public function testCheckIfIpAddressAllowedInvalidIp()
    {
        Yii::$app->request->method('getUserIP')->willReturn('10.0.0.1');

        $query = $this->createMock(ActiveQuery::class);
        $query->method('all')->willReturn([
            (object)[
                'ipAddress' => '192.168.1.0',
                'ipMask' => '255.255.255.0',
            ]
        ]);

        $task = $this->getMockBuilder(TaskResource::class)
            ->onlyMethods(['getIpRestrictions'])
            ->getMock();
        $task->method('getIpRestrictions')->willReturn($query);

        $this->expectException(ForbiddenHttpException::class);
        PermissionHelpers::checkIfTaskIpAddressAllowed($task);
    }
}
