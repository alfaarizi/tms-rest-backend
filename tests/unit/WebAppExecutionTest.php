<?php

namespace app\tests\unit;

use app\models\StudentFile;
use app\models\User;
use app\models\WebAppExecution;
use app\tests\unit\fixtures\WebAppExecutionFixture;
use Yii;

class WebAppExecutionTest extends \Codeception\Test\Unit
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

    /** @specify  */
    private WebAppExecution $webAppExecution;

    /** @specify  */
    private User $user;

    /** @specify  */
    private StudentFile $studentFile;

    protected function _before()
    {
        $this->user = $this->tester->grabRecord('app\models\User', ['id' => 1006]);
        $this->studentFile = $this->tester->grabRecord('app\models\StudentFile', ['id' => 1]);

        $this->webAppExecution = new WebAppExecution();
        $this->webAppExecution->port = 8080;
        $this->webAppExecution->dockerHostUrl = 'http://tms.elte.hu';
        $this->webAppExecution->instructorID = $this->user->id;
        $this->webAppExecution->studentFileID = $this->studentFile->id;
        $this->webAppExecution->containerName = 'myContainer';
    }

    // tests
    public function testValidation()
    {
        $this->specify('port must be set', function () {
            unset($this->webAppExecution->port);
            $this->assertFalse($this->webAppExecution->validate());
        });

        $this->specify('studentFileID must be set', function () {
            unset($this->webAppExecution->studentFileID);
            $this->assertFalse($this->webAppExecution->validate());
        });

        $this->specify('instructorID must be set', function () {
            unset($this->webAppExecution->instructorID);
            $this->assertFalse($this->webAppExecution->validate());
        });

        $this->webAppExecution->validate();
        print_r($this->webAppExecution->errors);
    }

    public function testExecutionsOf()
    {
        $webAppExecutions = WebAppExecution::find()->executionsOf($this->studentFile, $this->user->id)->all();
        $this->tester->assertCount(1, $webAppExecutions);
    }

    public function testExpired()
    {
        $webAppExecutions = WebAppExecution::find()->expired()->all();
        $this->tester->assertCount(1, $webAppExecutions);
    }

    public function testUrl()
    {
        $this->specify("When gateway not enabled [dockerHost]:[port] returned as url", function () {
            self::assertEquals('http://tms.elte.hu:8080', $this->webAppExecution->url);
        });

        $this->specify("When gateway enabled [gatewayUrl]/[containerName] returned as url", function () {
            Yii::$app->params['evaluator']['webApp']['gateway']['enabled'] = true;
            Yii::$app->params['evaluator']['webApp']['gateway']['url'] = 'https://gateway.com/app';
            self::assertEquals('https://gateway.com/app/myContainer', $this->webAppExecution->url);
        });
    }
}
