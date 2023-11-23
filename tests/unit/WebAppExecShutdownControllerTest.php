<?php

namespace app\tests\unit;

use app\commands\WebAppExecShutdownController;
use app\models\WebAppExecution;
use app\tests\unit\fixtures\WebAppExecutionFixture;

class WebAppExecShutdownControllerTest extends \Codeception\Test\Unit
{
    use \Codeception\Specify;

    public function _fixtures()
    {
        return [
            'webAppExecution' => [
                'class' => WebAppExecutionFixture::class
            ]
        ];
    }

    /**
     * @var \UnitTester
     */
    protected $tester;

    private WebAppExecShutdownController $controller;


    protected function _before()
    {
        $this->controller = new WebAppExecShutdownController(null, null);
    }

    // tests
    public function testShutDownExpiredWebAppExecutions()
    {
        $this->specify('Shut down expired only', function () {
            $this->tester->seeRecord(WebAppExecution::class, ['id' => 1]);
            $this->tester->canSeeRecord(WebAppExecution::class, ['id' => 2]);

            $this->controller->actionShutDownExpiredWebAppExecutions();

            $this->tester->cantSeeRecord(WebAppExecution::class, ['id' => 1]);
            $this->tester->canSeeRecord(WebAppExecution::class, ['id' => 2]);
        });
    }
}
