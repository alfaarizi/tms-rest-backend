<?php

namespace unit;

use app\components\UnitConverterHelper;

class UnitConverterHelperTest extends \Codeception\Test\Unit
{
    public function testPhpFilesizeToBytesWithoutUnit()
    {
        $this->assertEquals(2, UnitConverterHelper::phpFilesizeToBytes('2'));
    }

    public function testPhpFilesizeToBytesLowercase()
    {
        $this->assertEquals(2 * 1024, UnitConverterHelper::phpFilesizeToBytes('2k'));
        $this->assertEquals(2 * 1024 * 1024, UnitConverterHelper::phpFilesizeToBytes('2m'));
        $this->assertEquals(2 * 1024 * 1024 * 1024, UnitConverterHelper::phpFilesizeToBytes('2g'));
    }

    public function testPhpFilesizeToBytesUppercase()
    {
        $this->assertEquals(2 * 1024, UnitConverterHelper::phpFilesizeToBytes('2K'));
        $this->assertEquals(2 * 1024 * 1024, UnitConverterHelper::phpFilesizeToBytes('2M'));
        $this->assertEquals(2 * 1024 * 1024 * 1024, UnitConverterHelper::phpFilesizeToBytes('2G'));
    }
}
