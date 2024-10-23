<?php

namespace app\tests\unit;

use app\components\CodeCompassHelper;
use app\models\CodeCompassInstance;
use app\tests\unit\fixtures\CodeCompassInstanceFixture;
use Codeception\Test\Unit;

class CodeCompassHelperTest extends Unit
{
    public function _fixtures(): array
    {
        return [
            'codecompassinstances' => [
                'class' => CodeCompassInstanceFixture::class
            ]
        ];
    }

    public function testIsTooManyContainersRunning()
    {
        $this->assertFalse(CodeCompassHelper::isTooManyContainersRunning());

        $instance = new CodeCompassInstance();
        $instance->submissionId = 1;
        $instance->status = CodeCompassInstance::STATUS_RUNNING;
        $instance->instanceStarterUserId = 1006;
        $instance->save();

        $this->assertTrue(CodeCompassHelper::isTooManyContainersRunning());
    }

    public function testContainerAlreadyRunningWithRunningContainer()
    {
        $this->assertTrue(CodeCompassHelper::isContainerAlreadyRunning('1'));
    }

    public function testContainerAlreadyRunningWithStartingContainer()
    {
        $this->assertFalse(CodeCompassHelper::isContainerAlreadyRunning('2'));
    }

    public function testContainerAlreadyRunningWithNotExistingContainer()
    {
        $this->assertFalse(CodeCompassHelper::isContainerAlreadyRunning('0'));
    }

    public function testIsContainerCurrentlyStartingWithStartingContainer()
    {
        $this->assertTrue(CodeCompassHelper::isContainerCurrentlyStarting('2'));
    }

    public function testIsContainerCurrentlyStartingWithRunningContainer()
    {
        $this->assertFalse(CodeCompassHelper::isContainerCurrentlyStarting('1'));
    }

    public function testIsContainerCurrentlyStartingWithNotExistingContainer()
    {
        $this->assertFalse(CodeCompassHelper::isContainerCurrentlyStarting('0'));
    }

    public function testSelectFirstAvailablePort()
    {
        $this->assertEquals(25568, CodeCompassHelper::selectFirstAvailablePort());

        $instance = new CodeCompassInstance();
        $instance->submissionId = 1;
        $instance->status = CodeCompassInstance::STATUS_RUNNING;
        $instance->instanceStarterUserId = 1006;
        $instance->port = 25568;
        $instance->save();

        $this->assertNull(CodeCompassHelper::selectFirstAvailablePort());
    }
}
