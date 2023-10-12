<?php

namespace unit;

use app\commands\CodeCheckerController;
use app\components\codechecker\AnalyzerRunner;
use app\components\codechecker\AnalyzerRunnerFactory;
use app\components\codechecker\CodeCheckerResultPersistence;
use app\components\codechecker\CodeCheckerRunner;
use app\components\codechecker\StudentFileToAnalyzeFinder;
use app\components\docker\DockerImageManager;
use app\exceptions\CodeCheckerPersistenceException;
use app\exceptions\CodeCheckerResultNotifierException;
use app\exceptions\CodeCheckerRunnerException;
use app\models\StudentFile;
use app\models\Task;
use app\tests\unit\fixtures\CodeCheckerResultFixture;
use app\tests\unit\fixtures\StudentFilesFixture;
use app\tests\unit\fixtures\TaskFixture;
use Yii;
use yii\base\Module;
use yii\console\ExitCode;

class CodeCheckerControllerTest extends \Codeception\Test\Unit
{
    protected \UnitTester $tester;
    private $runnerMock;

    public function _fixtures(): array
    {
        return [
            'task' => [
                'class' => TaskFixture::class
            ],
            'studentfiles' => [
                'class' => StudentFilesFixture::class,
            ],
            'codecheckerresults' => [
                'class' => CodeCheckerResultFixture::class,
            ]
        ];
    }

    protected function _before()
    {
        $this->tester->copyDir(codecept_data_dir("appdata_samples"), Yii::getAlias("@appdata"));

        $finderMock = $this->createMock(StudentFileToAnalyzeFinder::class);
        $finderMock->method('findNext')
            ->willReturnOnConsecutiveCalls(
                $this->tester->grabRecord(StudentFile::class, ['id' => 1]),
                $this->tester->grabRecord(StudentFile::class, ['id' => 17])
            );

        Yii::$container->set(StudentFileToAnalyzeFinder::class, $finderMock);

        $this->runnerMock = $this->getMockBuilder(CodeCheckerRunner::class)
            ->enableOriginalConstructor()
            ->setConstructorArgs([$this->tester->grabRecord(StudentFile::class, ['id' => 8])])
            ->getMock();
    }

    protected function _after()
    {
        $this->tester->deleteDir(Yii::getAlias("@appdata"));
    }

    public function testCheck()
    {
        $this->runnerMock
            ->expects($this->exactly(2))
            ->method('run')
            ->willReturn(
                [
                    'tarPath' => codecept_data_dir('codechecker_samples/valid_cpp_reports.tar'),
                    'exitCode' => 1,
                    'stdout' => '',
                    'stderr' => ''
                ]
            );
        $this->runnerMock->expects($this->exactly(2))->method('deleteWorkDirectory');
        Yii::$container->set(AnalyzerRunner::class, $this->runnerMock);

        $persistenceMock = $this->createMock(CodeCheckerResultPersistence::class);
        $persistenceMock->expects($this->exactly(2))->method('saveResult');
        $persistenceMock->expects($this->never())->method('saveRunnerError');
        Yii::$container->set(CodeCheckerResultPersistence::class, $persistenceMock);

        $controller = new CodeCheckerController('null', new Module('test'));

        $controller->actionCheck(10); // The maximum count is 10, but there is actually 2 student files to check
    }

    public function testCheckWithRunnerException()
    {
        $this->runnerMock
            ->expects($this->exactly(2))
            ->method('run')
            ->willThrowException(new CodeCheckerRunnerException('Runner Exception'));
        $this->runnerMock->expects($this->exactly(2))->method('deleteWorkDirectory');
        Yii::$container->set(AnalyzerRunner::class, $this->runnerMock);

        $persistenceMock = $this->createMock(CodeCheckerResultPersistence::class);
        $persistenceMock->expects($this->never())->method('saveResult');
        $persistenceMock->expects($this->exactly(2))->method('saveRunnerError');
        Yii::$container->set(CodeCheckerResultPersistence::class, $persistenceMock);

        $controller = new CodeCheckerController('null', new Module('test'));

        $controller->actionCheck(10); // The maximum count is 10, but there is actually 2 student files to check
    }

    public function testCheckWithRunnerExceptionFailedToSaveError()
    {
        $this->runnerMock
            ->expects($this->exactly(2))
            ->method('run')
            ->willThrowException(new CodeCheckerRunnerException('Runner Exception'));
        $this->runnerMock->expects($this->exactly(2))->method('deleteWorkDirectory');
        Yii::$container->set(AnalyzerRunner::class, $this->runnerMock);

        $persistenceMock = $this->createMock(CodeCheckerResultPersistence::class);
        $persistenceMock->expects($this->never())->method('saveResult');
        $persistenceMock
            ->expects($this->exactly(2))
            ->method('saveRunnerError')
            ->willThrowException(new CodeCheckerPersistenceException("Persistence exception"));
        Yii::$container->set(CodeCheckerResultPersistence::class, $persistenceMock);

        $controller = new CodeCheckerController('null', new Module('test'));

        $controller->actionCheck(10); // The maximum count is 10, but there is actually 2 student files to check
    }

    public function testCheckWithRunnerExceptionFailedToSendNotification()
    {
        $this->runnerMock
            ->expects($this->exactly(2))
            ->method('run')
            ->willThrowException(new CodeCheckerRunnerException('Runner Exception'));
        $this->runnerMock->expects($this->exactly(2))->method('deleteWorkDirectory');
        Yii::$container->set(AnalyzerRunner::class, $this->runnerMock);

        $persistenceMock = $this->createMock(CodeCheckerResultPersistence::class);
        $persistenceMock->expects($this->never())->method('saveResult');
        $persistenceMock
            ->expects($this->exactly(2))
            ->method('saveRunnerError')
            ->willThrowException(new CodeCheckerResultNotifierException("Notifier exception"));
        Yii::$container->set(CodeCheckerResultPersistence::class, $persistenceMock);

        $controller = new CodeCheckerController('null', new Module('test'));

        $controller->actionCheck(10); // The maximum count is 10, but there is actually 2 student files to check
    }

    public function testCheckWithPersistenceException()
    {
        $this->runnerMock
            ->expects($this->exactly(2))
            ->method('run')
            ->willReturn(
                [
                    'tarPath' => codecept_data_dir('codechecker_samples/valid_cpp_reports.tar'),
                    'exitCode' => 1,
                    'stdout' => '',
                    'stderr' => ''
                ]
            );

        $this->runnerMock->expects($this->exactly(2))->method('deleteWorkDirectory');
        Yii::$container->set(AnalyzerRunner::class, $this->runnerMock);

        $persistenceMock = $this->createMock(CodeCheckerResultPersistence::class);
        $persistenceMock
            ->expects($this->exactly(2))
            ->method('saveResult')
            ->willThrowException(new CodeCheckerRunnerException('Persistence error'));
        Yii::$container->set(CodeCheckerResultPersistence::class, $persistenceMock);

        $controller = new CodeCheckerController('null', new Module('test'));
        $controller->actionCheck(10);
    }

    public function testCheckWithNotifierException()
    {
        $this->runnerMock
            ->expects($this->exactly(2))
            ->method('run')
            ->willReturn(
                [
                    'tarPath' => codecept_data_dir('codechecker_samples/valid_cpp_reports.tar'),
                    'exitCode' => 1,
                    'stdout' => '',
                    'stderr' => ''
                ]
            );

        $this->runnerMock->expects($this->exactly(2))->method('deleteWorkDirectory');
        Yii::$container->set(AnalyzerRunner::class, $this->runnerMock);

        $persistenceMock = $this->createMock(CodeCheckerResultPersistence::class);
        $persistenceMock
            ->expects($this->exactly(2))
            ->method('saveResult')
            ->willThrowException(new CodeCheckerResultNotifierException('Notifier error'));
        Yii::$container->set(CodeCheckerResultPersistence::class, $persistenceMock);

        $controller = new CodeCheckerController('null', new Module('test'));
        $controller->actionCheck(10);
    }

    public function testPullReportConverterImage()
    {
        $dockerImageManagerMock = $this->createMock(DockerImageManager::class);
        $dockerImageManagerMock->method('pullImage');
        Yii::$container->set(DockerImageManager::class, $dockerImageManagerMock);
        $controller = new CodeCheckerController('null', new Module('test'));

        $exitCode = $controller->actionPullReportConverterImage('linux');

        $this->assertEquals(ExitCode::OK, $exitCode);
    }

    public function testPullReportConverterImageInvalidOs()
    {
        $controller = new CodeCheckerController('null', new Module('test'));

        $exitCode = $controller->actionPullReportConverterImage('unknown');

        $this->assertEquals(ExitCode::USAGE, $exitCode);
    }
}
