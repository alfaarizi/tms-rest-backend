<?php
namespace Helper;

// here you can define custom actions
// all public methods declared in helper class will be available in $I

use Codeception\TestInterface;

class Api extends \Codeception\Module
{
    public function _before(TestInterface $test)
    {
        parent::_before($test);
        /** @var \Codeception\Module\Yii2 $module */
        $module = $this->getModule('Yii2');
        $module->client->setServerParameter('REMOTE_ADDR', '192.168.1.1');
    }
}
