<?php

namespace app\tests\unit;

use app\commands\CronController;
use app\models\WebAppExecution;
use app\tests\unit\fixtures\WebAppExecutionFixture;
use Yii;

class CronControllerTest extends \Codeception\Test\Unit
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

    private CronController $cronController;


    protected function _before()
    {
        $this->cronController = new CronController(null, null);
    }

    // tests
    public function testShutDownExpiredWebAppExecutions()
    {
        $this->specify('Shut down expired only', function () {
            $this->tester->seeRecord(WebAppExecution::class, ['id' => 1]);
            $this->tester->canSeeRecord(WebAppExecution::class, ['id' => 2]);

            $this->cronController->actionShutDownExpiredWebAppExecutions();

            $this->tester->cantSeeRecord(WebAppExecution::class, ['id' => 1]);
            $this->tester->canSeeRecord(WebAppExecution::class, ['id' => 2]);
        });
    }
}
