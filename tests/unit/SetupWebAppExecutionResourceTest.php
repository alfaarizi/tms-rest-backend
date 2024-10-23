<?php

namespace app\tests\unit;

use app\modules\instructor\resources\SetupWebAppExecutionResource;
use Yii;

class SetupWebAppExecutionResourceTest extends \Codeception\Test\Unit
{
    use \Codeception\Specify;

    /**
     * @var \UnitTester
     */
    protected $tester;


    /** @specify  */
    private SetupWebAppExecutionResource $data;


    // tests
    public function testValidation()
    {
        $this->data = new SetupWebAppExecutionResource();
        $this->data->submissionID = 1;
        $this->data->runInterval = 30;

        $this->specify('student file id must be set', function () {
            $this->data->submissionID = null;
            $this->assertFalse($this->data->validate());
        });

        $this->specify('run interval must be >= 10', function () {
            $this->data->runInterval = 9;
            $this->assertFalse($this->data->validate());
        });

        $this->specify('run interval must be <= 60', function () {
            $this->data->runInterval = 61;
            $this->assertFalse($this->data->validate());
        });

        $this->specify('Default run interval is from config', function () {
            $this->data->runInterval = null;
            $this->assertTrue($this->data->validate());
            $this->assertEquals(Yii::$app->params['evaluator']['webApp']['maxWebAppRunTime'], $this->data->runInterval);
        });

        $this->assertTrue($this->data->validate());
    }
}
