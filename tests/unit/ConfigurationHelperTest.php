<?php

namespace unit;

use app\components\ConfigurationHelper;

class ConfigurationHelperTest extends \Codeception\Test\Unit
{
    public function testTempFolderPaths()
    {
        $this->assertEquals('@runtime/./tmp/test', ConfigurationHelper::checkTempPath('./tmp/test'));
        $this->assertEquals('@runtime/tmp/test', ConfigurationHelper::checkTempPath('tmp/test'));
        $this->assertEquals(sys_get_temp_dir() . '/tms', ConfigurationHelper::checkTempPath(null));
        if (PHP_OS_FAMILY === 'Windows') {
            $this->assertEquals('C:\\\\tmp', ConfigurationHelper::checkTempPath('C:\\\\tmp'));
        } else {
            $this->assertEquals('/tmp/test', ConfigurationHelper::checkTempPath('/tmp/test'));
        }
    }
}
