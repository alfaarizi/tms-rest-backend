<?php

namespace app\tests\unit;

use app\components\docker\DockerContainer;
use app\components\SubmissionRunner;
use app\exceptions\SubmissionRunnerException;
use app\models\Submission;
use app\models\User;
use app\models\WebAppExecution;
use app\modules\instructor\components\exception\WebAppExecutionException;
use app\modules\instructor\components\WebAppExecutor;
use app\modules\instructor\resources\SetupWebAppExecutionResource;
use app\tests\doubles\DockerStub;
use app\tests\unit\fixtures\WebAppExecutionFixture;
use Docker\API\Model\ContainersIdJsonGetResponse200;
use Docker\API\Model\SystemInfo;
use Docker\Docker;
use GuzzleHttp\Psr7\Response;
use Yii;
use yii\db\Exception;

class WebAppExecutorTest extends \Codeception\Test\Unit
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

    private WebAppExecutor $webAppExecutor;

    /** @specify  */
    private User $user;

    /** @specify  */
    private SetupWebAppExecutionResource $setupData;

    /**
     * @var SubmissionRunner|\PHPUnit\Framework\MockObject\MockObject
     */
    private $submissionRunnerMock;


    protected function _before()
    {
        $this->user = $this->tester->grabRecord(User::class, ['id' => 1006]);

        $this->setupData = new SetupWebAppExecutionResource();
        $this->setupData->runInterval = 60;

        $this->submissionRunnerMock = $this->makeEmpty(SubmissionRunner::class);
        $this->webAppExecutor = new WebAppExecutor($this->submissionRunnerMock);

        Yii::$app->params['backendUrl'] = 'https://tms.elte.hu';
    }

    // tests
    public function testStartWebApplicationValidation()
    {
        $this->specify('Instance already running', function () {
            $this->tester->expectThrowable(WebAppExecutionException::class, function () {
                $submission = $this->tester->grabRecord(Submission::class, ['id' => 1]);
                $this->webAppExecutor->startWebApplication($submission, $this->user->id, $this->setupData);
            });
        });

        $this->specify('Task not web app', function () {
            $this->tester->expectThrowable(WebAppExecutionException::class, function () {
                $submission = $this->tester->grabRecord(Submission::class, ['id' => 2]);
                $this->webAppExecutor->startWebApplication($submission, $this->user->id, $this->setupData);
            });
        });

        $this->specify("When student file compilation failed already, then start fails", function () {
            Yii::$app->params['evaluator']['webApp']['linux']['reservedPorts'] = array('from' => 8081, 'to' => 8083);
            Yii::$app->params['evaluator']['webApp']['windows']['reservedPorts'] = array('from' => 8081, 'to' => 8083);
            $submission = Submission::findOne(['id' => 3]);
            $submission->task->appType = 'Web';

            $this->tester->expectThrowable(WebAppExecutionException::class, function () use ($submission) {
                $this->webAppExecutor->startWebApplication($submission, $this->user->id, $this->setupData);
            });
        });
    }

    public function testStartWebApplicationFailure()
    {
        $this->specify("When all ports reserved reservation fails", function () {
            Yii::$app->params['evaluator']['webApp']['linux']['reservedPorts'] = array('from' => 8081, 'to' => 8082);
            Yii::$app->params['evaluator']['webApp']['windows']['reservedPorts'] = array('from' => 8081, 'to' => 8082);
            $submission = Submission::findOne(['id' => 2]);
            $submission->task->appType = 'Web';

            $this->tester->expectThrowable(WebAppExecutionException::class, function () use ($submission) {
                $this->webAppExecutor->startWebApplication($submission, $this->user->id, $this->setupData);
            });
        });

        $this->specify("When compile fails records should be cleaned up, compile failure must saved", function () {

            $errors = [
                'exitCode' => 1,
                'stdout' => 'out',
                'stderr' => 'err'
            ];
            $this->submissionRunnerMock
                ->method('run')
                ->willThrowException(
                    new SubmissionRunnerException(
                        '',
                        SubmissionRunnerException::COMPILE_FAILURE,
                        $errors
                    )
                );

            Yii::$app->params['evaluator']['webApp']['linux']['reservedPorts'] = array('from' => 8081, 'to' => 8083);
            Yii::$app->params['evaluator']['webApp']['windows']['reservedPorts'] = array('from' => 8081, 'to' => 8083);
            $submission = Submission::findOne(['id' => 4]);
            $submission->task->appType = 'Web';
            $submission->task->testOS = 'linux';

            $this->tester->expectThrowable(WebAppExecutionException::class, function () use ($submission) {
                $this->webAppExecutor->startWebApplication($submission, $this->user->id, $this->setupData);
            });
            $this->tester->cantSeeRecord(WebAppExecution::class, ['port' => 8081]);
            $this->tester->cantSeeRecord(WebAppExecution::class, ['port' => 8082]);

            $record = $this->tester->grabRecord(Submission::class, ['id' => 4]);
            self::assertEquals('Compilation Failed', $record->autoTesterStatus);
            self::assertEquals('Failed', $record->status);
            self::assertEquals('out' . PHP_EOL . 'err', $record->errorMsg);
        });
    }

    public function testStartWebApplicationSuccess()
    {
        $containerName = 'myTestContainer';
        $this->submissionRunnerMock
            ->method('run')
            ->willReturn(
                $this->makeEmpty(DockerContainer::class, ['getContainerName' => $containerName])
            );
        Yii::$app->params['evaluator']['webApp']['linux']['reservedPorts'] = array('from' => 8081, 'to' => 8083);
        Yii::$app->params['evaluator']['webApp']['windows']['reservedPorts'] = array('from' => 8081, 'to' => 8083);
        $submission = Submission::findOne(['id' => 4]);
        $submission->task->appType = 'Web';

        if (!empty(Yii::$app->params['evaluator']['linux'])) {
            $submission->task->testOS = 'linux';
        } else if (!empty(Yii::$app->params['evaluator']['windows'])) {
            $submission->task->testOS = 'windows';
        }

        $webAppExecutionResource = $this->webAppExecutor
            ->startWebApplication($submission, $this->user->id, $this->setupData);

        $record = $this->tester->grabRecord(WebAppExecution::class, ['dockerHostUrl' => 'https://tms.elte.hu', 'port' => 8081]);
        self::assertNotEmpty($record, 'Record must not be null');
        self::assertEquals($containerName, $record->containerName);
        self::assertEquals($submission->id, $record->submissionID);
        self::assertEquals($this->user->id, $record->instructorID);
        self::assertEquals(8081, $record->port);
        self::assertEquals(Yii::$app->params['backendUrl'], $record->dockerHostUrl);
        self::assertNotEmpty($record->startedAt);
        self::assertNotEmpty($record->shutdownAt);

        self::assertEquals($containerName, $webAppExecutionResource->containerName);
        self::assertEquals(8081, $webAppExecutionResource->port);
        self::assertEquals('https://tms.elte.hu:8081', $webAppExecutionResource->url);
        self::assertNotEmpty($webAppExecutionResource->startedAt);
        self::assertNotEmpty($webAppExecutionResource->shutdownAt);
        //self::assertObjectNotHasAttribute('dockerHostUrl', $webAppExecutionResource); // deprecated
        // todo: use assertObjectHasNotProperty(), once it becomes available. Until then:
        self::assertIsObject($webAppExecutionResource);
        self::assertFalse(property_exists($webAppExecutionResource, 'dockerHostUrl'));
    }

    public function testStopWebApplication()
    {
        $body = [];
        $body['Id'] = 'foo';
        $response200 = new Response(200, [], json_encode($body));

        $mockObject = $this->makeEmpty(DockerStub::class);
        $mockObject->method('systemInfo')->willReturn(new SystemInfo());
        $mockObject->method('containerInspect')->willReturn($response200);
        Yii::$container->set(Docker::class, $mockObject);

        $mockObject->method('containerStop')->willReturn('');
        $mockObject->method('containerDelete')->willReturn('');
        $this->specify("When shut down succeeds delete web app", function () {
            $record = $this->tester->grabRecord(WebAppExecution::class, ['id' => 1]);
            $this->webAppExecutor->stopWebApplication($record);
            $this->tester->cantSeeRecord(WebAppExecution::class, ['id' => 1]);
        });

        $body = [];
        $body['Id'] = 'foo';
        $response200 = new Response(200, [], json_encode($body));
        $mockObject = $this->makeEmpty(DockerStub::class);
        $mockObject->method('systemInfo')->willReturn(new SystemInfo());
        $mockObject->method('containerInspect')->willReturn($response200);
        Yii::$container->set(Docker::class, $mockObject);
        $mockObject->method('containerKill')->willThrowException(new Exception(''));
        $this->specify("When shut down fails, keep web app in record", function () {
            $record = $this->tester->grabRecord(WebAppExecution::class, ['id' => 2]);
            $this->tester->expectThrowable(WebAppExecutionException::class, function () use ($record) {
                $this->webAppExecutor->stopWebApplication($record);
            });
            $this->tester->canSeeRecord(WebAppExecution::class, ['id' => 2]);
        });
    }

    public function testIsDockerHostLocal()
    {
        $this->specify("When url is localhost, loopback or unix then docker is localhost", function () {
            Yii::$app->params['evaluator']['linux'] = 'tcp://127.0.0.1:42067';
            self::assertTrue(WebAppExecutor::isDockerHostLocal('linux'));

            Yii::$app->params['evaluator']['linux'] = 'tcp://localhost:42067';
            self::assertTrue(WebAppExecutor::isDockerHostLocal('linux'));

            Yii::$app->params['evaluator']['linux'] = 'unix:///var/run/docker.sock';
            self::assertTrue(WebAppExecutor::isDockerHostLocal('linux'));
        });

        $this->specify("When url matches backend url, then docker is localhost", function () {
            Yii::$app->params['evaluator']['linux'] = 'tcp://tms.elte.hu:42067';
            Yii::$app->params['backendUrl'] = 'http://tms.elte.hu/web/backed';
            self::assertTrue(WebAppExecutor::isDockerHostLocal('linux'));

            Yii::$app->params['evaluator']['linux'] = 'tcp://tms.elte.hu:42067';
            Yii::$app->params['backendUrl'] = 'https://tms.elte.hu/web/backed';
            self::assertTrue(WebAppExecutor::isDockerHostLocal('linux'));
        });

        $this->specify("When url differs from backend url, then docker is not localhost", function () {
            Yii::$app->params['evaluator']['linux'] = 'tcp://192.168.0.1:42067';
            Yii::$app->params['backendUrl'] = 'https://tms.elte.hu/web/backed';
            self::assertFalse(WebAppExecutor::isDockerHostLocal('linux'));
        });
    }
}
