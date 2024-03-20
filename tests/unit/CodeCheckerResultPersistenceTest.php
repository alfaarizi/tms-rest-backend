<?php

namespace app\tests\unit;

use app\components\codechecker\CodeCheckerResultNotifier;
use app\components\codechecker\CodeCheckerResultPersistence;
use app\exceptions\CodeCheckerPersistenceException;
use app\models\CodeCheckerReport;
use app\models\CodeCheckerResult;
use app\models\StudentFile;
use app\tests\unit\fixtures\CodeCheckerResultFixture;
use app\tests\unit\fixtures\StudentFilesFixture;
use app\tests\unit\fixtures\TaskFixture;
use Yii;
use yii\helpers\Console;
use yii\helpers\VarDumper;

class CodeCheckerResultPersistenceTest extends \Codeception\Test\Unit
{
    protected \UnitTester $tester;

    public function _fixtures(): array
    {
        return [
            'tasks' => [
                'class' => TaskFixture::class,
            ],
            'studentfiles' => [
                'class' => StudentFilesFixture::class,
            ],
            'codecheckerresults' => [
                'class' => CodeCheckerResultFixture::class
            ]
        ];
    }

    protected function _after()
    {
        $this->tester->deleteDir(Yii::getAlias("@appdata"));
    }

    public function testCreateNewResult()
    {
        $studentFile = $this->tester->grabRecord(StudentFile::class, ['id' => 5]);

        $notifierMock = $this->createMock(CodeCheckerResultNotifier::class);
        $notifierMock->expects($this->never())->method('sendNotifications');
        Yii::$container->set(CodeCheckerResultNotifier::class, $notifierMock);

        $persistence = new CodeCheckerResultPersistence($studentFile);

        $persistence->createNewResult();

        $this->tester->seeRecord(StudentFile::class, ['id' => 5]);
        $this->assertNotNull($studentFile->codeCheckerResult);
        $this->assertEquals(CodeCheckerResult::STATUS_IN_PROGRESS, $studentFile->codeCheckerResult->status);
        $this->assertEquals($studentFile->id, $studentFile->codeCheckerResult->studentFileID);
        $this->assertEmpty($studentFile->codeCheckerResult->codeCheckerReports);
    }

    public function testTryToCreateNewResultExists()
    {
        $studentFile = $this->tester->grabRecord(StudentFile::class, ['id' => 1]);
        $originalId = $studentFile->codeCheckerResultID;

        $notifierMock = $this->createMock(CodeCheckerResultNotifier::class);
        $notifierMock->expects($this->never())->method('sendNotifications');
        Yii::$container->set(CodeCheckerResultNotifier::class, $notifierMock);
        $persistence = new CodeCheckerResultPersistence($studentFile);

        $this->expectException(CodeCheckerPersistenceException::class);

        $persistence->createNewResult();

        // Check if result id has not changed
        $this->assertEquals($originalId, StudentFile::findOne(1)->codeCheckerResultID);
    }

    public function testTryToSaveAlreadySavedResult()
    {
        $studentFile = $this->tester->grabRecord(StudentFile::class, ['id' => 1]);
        $originalStatus = $studentFile->codeCheckerResult->status;
        $originalStdout = $studentFile->codeCheckerResult->stdout;
        $originalStderr = $studentFile->codeCheckerResult->stderr;

        $notifierMock = $this->createMock(CodeCheckerResultNotifier::class);
        $notifierMock->expects($this->never())->method('sendNotifications');
        Yii::$container->set(CodeCheckerResultNotifier::class, $notifierMock);

        $persistence = new CodeCheckerResultPersistence($studentFile);

        $this->expectException(CodeCheckerPersistenceException::class);

        $persistence->saveResult(null, 1, 'stdout', 'stderr');

        // Check if result has not changed
        $result = $this->tester->grabRecord(CodeCheckerResult::class, ['id' => '1-result1']);
        $this->assertEquals($originalStatus, $result->status);
        $this->assertEquals($originalStdout, $result->stdout);
        $this->assertEquals($originalStderr, $result->stderr);
        $this->assertNull($result->runnerErrorMessage);
    }

    public function testTryToSaveWithInvalidArguments()
    {
        $studentFile = $this->tester->grabRecord(StudentFile::class, ['id' => 6]);

        $notifierMock = $this->createMock(CodeCheckerResultNotifier::class);
        $notifierMock->expects($this->never())->method('sendNotifications');
        Yii::$container->set(CodeCheckerResultNotifier::class, $notifierMock);

        $persistence = new CodeCheckerResultPersistence($studentFile);

        $this->expectException(\InvalidArgumentException::class);

        $persistence->saveResult('path_to_tar', 0, 'stdout', 'stderr');

        // Check if result has not changed
        $result = $this->tester->grabRecord(CodeCheckerResult::class, ['id' => $studentFile->codeCheckerResultID]);
        $this->assertEquals(CodeCheckerResult::STATUS_IN_PROGRESS, $result->status);
        $this->assertNull($result->stdout);
        $this->assertNull($result->stderr);
        $this->assertNull($result->runnerErrorMessage);
    }

    public function testTryToSaveToAFileWithoutResult()
    {
        $studentFile = $this->tester->grabRecord(StudentFile::class, ['id' => 2]);
        $persistence = new CodeCheckerResultPersistence($studentFile);

        $notifierMock = $this->createMock(CodeCheckerResultNotifier::class);
        $notifierMock->expects($this->never())->method('sendNotifications');
        Yii::$container->set(CodeCheckerResultNotifier::class, $notifierMock);

        $this->expectException(CodeCheckerPersistenceException::class);

        $persistence->saveResult(null, 1, 'stdout', 'stderr');
    }

    public function testSaveNoIssuesResult()
    {
        $studentFile = $this->tester->grabRecord(StudentFile::class, ['id' => 6]);

        $notifierMock = $this->createMock(CodeCheckerResultNotifier::class);
        $notifierMock->expects($this->once())->method('sendNotifications');
        Yii::$container->set(CodeCheckerResultNotifier::class, $notifierMock);

        $persistence = new CodeCheckerResultPersistence($studentFile);

        $persistence->saveResult(null, 0, 'stdout', '');

        $result = $this->tester->grabRecord(CodeCheckerResult::class, ['id' => $studentFile->codeCheckerResultID]);
        $this->assertEquals(CodeCheckerResult::STATUS_NO_ISSUES, $result->status);
        $this->assertEquals('stdout', $result->stdout);
        $this->assertEquals('', $result->stderr);
        $this->assertEmpty($result->codeCheckerReports);
        $this->assertNull($result->runnerErrorMessage);
    }

    public function testAnalysisFailedNoTarFile()
    {
        $studentFile = $this->tester->grabRecord(StudentFile::class, ['id' => 6]);

        $notifierMock = $this->createMock(CodeCheckerResultNotifier::class);
        $notifierMock->expects($this->once())->method('sendNotifications');
        Yii::$container->set(CodeCheckerResultNotifier::class, $notifierMock);

        $persistence = new CodeCheckerResultPersistence($studentFile);

        $persistence->saveResult(null, 1, 'stdout', 'stderr');

        $result = $this->tester->grabRecord(CodeCheckerResult::class, ['id' => $studentFile->codeCheckerResultID]);
        $this->assertEquals(CodeCheckerResult::STATUS_ANALYSIS_FAILED, $result->status);
        $this->assertEquals('stdout', $result->stdout);
        $this->assertEquals('stderr', $result->stderr);
        $this->assertEmpty($result->codeCheckerReports);
        $this->assertNull($result->runnerErrorMessage);
    }

    public function testAnalysisFailedNoTarFileCanvas()
    {
        $studentFile = $this->tester->grabRecord(StudentFile::class, ['id' => 6]);

        $notifierMock = $this->createMock(CodeCheckerResultNotifier::class);
        $notifierMock->expects($this->once())->method('sendNotifications');
        Yii::$container->set(CodeCheckerResultNotifier::class, $notifierMock);

        $persistence = new CodeCheckerResultPersistence($studentFile);

        $persistence->saveResult(null, 1, 'stdout', 'stderr');

        $result = $this->tester->grabRecord(CodeCheckerResult::class, ['id' => $studentFile->codeCheckerResultID]);
        $this->assertEquals(CodeCheckerResult::STATUS_ANALYSIS_FAILED, $result->status);
        $this->assertEquals('stdout', $result->stdout);
        $this->assertEquals('stderr', $result->stderr);
        $this->assertEmpty($result->codeCheckerReports);
        $this->assertNull($result->runnerErrorMessage);
    }

    public function testAnalysisFailedEmptyJsonArray()
    {
        $studentFile = $this->tester->grabRecord(StudentFile::class, ['id' => 6]);

        $notifierMock = $this->createMock(CodeCheckerResultNotifier::class);
        $notifierMock->expects($this->once())->method('sendNotifications');
        Yii::$container->set(CodeCheckerResultNotifier::class, $notifierMock);

        $persistence = new CodeCheckerResultPersistence($studentFile);

        $persistence->saveResult(
            codecept_data_dir('codechecker_samples/valid_empty_json_array.tar'),
            1,
            'stdout',
            'stderr'
        );

        $result = $this->tester->grabRecord(CodeCheckerResult::class, ['id' => $studentFile->codeCheckerResultID]);
        $this->assertEquals(CodeCheckerResult::STATUS_ANALYSIS_FAILED, $result->status);
        $this->assertEquals('stdout', $result->stdout);
        $this->assertEquals('stderr', $result->stderr);
        $this->assertEmpty($result->codeCheckerReports);
        $this->assertNull($result->runnerErrorMessage);
    }

    public function testIssuesFoundResultWithTar()
    {
        $studentFile = $this->tester->grabRecord(StudentFile::class, ['id' => 6]);

        $notifierMock = $this->createMock(CodeCheckerResultNotifier::class);
        $notifierMock->expects($this->once())->method('sendNotifications');
        Yii::$container->set(CodeCheckerResultNotifier::class, $notifierMock);

        $persistence = new CodeCheckerResultPersistence($studentFile);

        $persistence->saveResult(
            codecept_data_dir('codechecker_samples/valid_cpp_reports.tar'),
            1,
            'stdout',
            'stderr'
        );

        $result = $this->tester->grabRecord(CodeCheckerResult::class, ['id' => $studentFile->codeCheckerResultID]);
        $this->assertEquals(CodeCheckerResult::STATUS_ISSUES_FOUND, $result->status);
        $this->assertEquals('stdout', $result->stdout);
        $this->assertEquals('stderr', $result->stderr);

        $this->assertEquals(2, count($result->codeCheckerReports));

        $this->tester->seeRecord(CodeCheckerReport::class, [
            'resultID' => 2,
            'reportHash' => '974e19b580a9a4168360b03c9a3b5ed9',
            'filePath' => 'main.cpp',
            'line' => 4,
            'column' => 9,
            'checkerName' => 'core.DivideZero',
            'analyzerName' => 'clangsa',
            'severity' => 'High',
            'category' => 'Logic error',
            'message' => 'Division by zero',
            'plistFileName' => 'main.cpp_clangsa_59e72f046a9227c75387df65f9099cf5.plist',
        ]);
        $this->tester->seeRecord(CodeCheckerReport::class, [
            'resultID' => 2,
            'reportHash' => '4c4524aa900b34a1674119960aaf63d7',
            'filePath' => 'main.cpp',
            'line' => 4,
            'column' => 3,
            'checkerName' => 'deadcode.DeadStores',
            'analyzerName' => 'clangsa',
            'severity' => 'Low',
            'category' => 'Dead store',
            'message' => "Value stored to 'a' is never read",
            'plistFileName' => 'main.cpp_clangsa_59e72f046a9227c75387df65f9099cf5.plist',
          ]);

        $htmlPath = $result->htmlReportsDirPath;
        $this->assertNotNull($htmlPath);
        $this->assertFileExists("$htmlPath/index.html");
        $this->assertFileExists("$htmlPath/statistics.html");
        $this->assertFileExists("$htmlPath/main.cpp_clangsa_59e72f046a9227c75387df65f9099cf5.plist.html");
    }

    public function testTryToIssuesFoundResultWithoutJsonReports()
    {
        $studentFile = $this->tester->grabRecord(StudentFile::class, ['id' => 6]);

        $notifierMock = $this->createMock(CodeCheckerResultNotifier::class);
        $notifierMock->expects($this->never())->method('sendNotifications');
        Yii::$container->set(CodeCheckerResultNotifier::class, $notifierMock);

        $persistence = new CodeCheckerResultPersistence($studentFile);

        $this->expectException(CodeCheckerPersistenceException::class);

        $persistence->saveResult(
            codecept_data_dir('codechecker_samples/without_json.tar'),
            1,
            'stdout',
            'stderr'
        );

        // Check if result has not changed
        $result = $this->tester->grabRecord(CodeCheckerResult::class, ['id' => $studentFile->codeCheckerResultID]);
        $this->assertEquals(CodeCheckerResult::STATUS_IN_PROGRESS, $result->status);
        $this->assertNull($result->stdout);
        $this->assertNull($result->stderr);
        $this->assertNull($result->runnerErrorMessage);
    }

    public function testTryToSaveIssuesFoundResultWithUnsupportedJsonVersion()
    {
        $studentFile = $this->tester->grabRecord(StudentFile::class, ['id' => 6]);

        $notifierMock = $this->createMock(CodeCheckerResultNotifier::class);
        $notifierMock->expects($this->never())->method('sendNotifications');
        Yii::$container->set(CodeCheckerResultNotifier::class, $notifierMock);

        $persistence = new CodeCheckerResultPersistence($studentFile);

        $this->expectException(CodeCheckerPersistenceException::class);

        $persistence->saveResult(
            codecept_data_dir('codechecker_samples/json_invalid_version.tar'),
            1,
            'stdout',
            'stderr'
        );

        // Check if result has not changed
        $result = CodeCheckerResult::findOne($studentFile->codeCheckerResultID);
        $this->assertEquals(CodeCheckerResult::STATUS_IN_PROGRESS, $result->status);
        $this->assertNull($result->stdout);
        $this->assertNull($result->stderr);
        $this->assertNull($result->runnerErrorMessage);
    }

    public function testTryToSaveIssuesFoundResultWithoutHtmlReports()
    {

        $studentFile = $this->tester->grabRecord(StudentFile::class, ['id' => 6]);

        $notifierMock = $this->createMock(CodeCheckerResultNotifier::class);
        $notifierMock->expects($this->never())->method('sendNotifications');
        Yii::$container->set(CodeCheckerResultNotifier::class, $notifierMock);

        $persistence = new CodeCheckerResultPersistence($studentFile);

        $this->expectException(CodeCheckerPersistenceException::class);
        $persistence->saveResult(
            codecept_data_dir('codechecker_samples/without_html.tar'),
            1,
            'stdout',
            'stderr'
        );

        // Check if result has not changed
        $result = CodeCheckerResult::findOne($studentFile->codeCheckerResultID);
        $this->assertEquals(CodeCheckerResult::STATUS_IN_PROGRESS, $result->status);
        $this->assertNull($result->stdout);
        $this->assertNull($result->stderr);
        $this->assertNull($result->runnerErrorMessage);
    }

    public function testSaveWithRunnerFailedStatus()
    {
        $studentFile = $this->tester->grabRecord(StudentFile::class, ['id' => 6]);

        $notifierMock = $this->createMock(CodeCheckerResultNotifier::class);
        $notifierMock->expects($this->once())->method('sendNotifications');
        Yii::$container->set(CodeCheckerResultNotifier::class, $notifierMock);

        $persistence = new CodeCheckerResultPersistence($studentFile);
        $persistence->saveRunnerError("Run failed");

        // Check if result has not changed
        $result = CodeCheckerResult::findOne($studentFile->codeCheckerResultID);
        $this->assertEquals(CodeCheckerResult::STATUS_RUNNER_ERROR, $result->status);
        $this->assertNull($result->stdout);
        $this->assertNull($result->stderr);
        $this->assertEquals("Run failed", $result->runnerErrorMessage);
    }

    public function testTryToSaveWithRunnerErrorWithoutResult()
    {
        $studentFile = $this->tester->grabRecord(StudentFile::class, ['id' => 2]);

        $notifierMock = $this->createMock(CodeCheckerResultNotifier::class);
        $notifierMock->expects($this->never())->method('sendNotifications');
        Yii::$container->set(CodeCheckerResultNotifier::class, $notifierMock);

        $persistence = new CodeCheckerResultPersistence($studentFile);

        $this->expectException(CodeCheckerPersistenceException::class);

        $persistence->saveRunnerError("Run failed");
    }

    public function testTryToSaveWithRunnerErrorAlreadySaved()
    {
        $studentFile = $this->tester->grabRecord(StudentFile::class, ['id' => 1]);

        $notifierMock = $this->createMock(CodeCheckerResultNotifier::class);
        $notifierMock->expects($this->never())->method('sendNotifications');
        Yii::$container->set(CodeCheckerResultNotifier::class, $notifierMock);

        $persistence = new CodeCheckerResultPersistence($studentFile);
        $originalStatus = $studentFile->codeCheckerResult->status;
        $originalRunnerErrorMessage = $studentFile->codeCheckerResult->runnerErrorMessage;

        $this->expectException(CodeCheckerPersistenceException::class);

        $persistence->saveResult(null, 1, 'stdout', 'stderr');

        // Check if result has not changed
        $result = CodeCheckerResult::findOne("1-result1");
        $this->assertEquals($originalStatus, $result->status);
        $this->assertEquals($originalRunnerErrorMessage, $result->runnerErrorMessage);
        $this->assertNull($result->runnerErrorMessage);
    }
}
