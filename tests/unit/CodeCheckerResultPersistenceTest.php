<?php

namespace app\tests\unit;

use app\components\codechecker\CodeCheckerResultNotifier;
use app\components\codechecker\CodeCheckerResultPersistence;
use app\exceptions\CodeCheckerPersistenceException;
use app\models\CodeCheckerReport;
use app\models\CodeCheckerResult;
use app\models\Submission;
use app\tests\unit\fixtures\CodeCheckerResultFixture;
use app\tests\unit\fixtures\SubmissionsFixture;
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
            'submission' => [
                'class' => SubmissionsFixture::class,
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
        $submission = $this->tester->grabRecord(Submission::class, ['id' => 5]);

        $notifierMock = $this->createMock(CodeCheckerResultNotifier::class);
        $notifierMock->expects($this->never())->method('sendNotifications');
        Yii::$container->set(CodeCheckerResultNotifier::class, $notifierMock);

        $persistence = new CodeCheckerResultPersistence($submission);

        $persistence->createNewResult();

        $this->tester->seeRecord(Submission::class, ['id' => 5]);
        $this->assertNotNull($submission->codeCheckerResult);
        $this->assertEquals(CodeCheckerResult::STATUS_IN_PROGRESS, $submission->codeCheckerResult->status);
        $this->assertEquals($submission->id, $submission->codeCheckerResult->submissionID);
        $this->assertEmpty($submission->codeCheckerResult->codeCheckerReports);
    }

    public function testTryToCreateNewResultExists()
    {
        $submission = $this->tester->grabRecord(Submission::class, ['id' => 1]);
        $originalId = $submission->codeCheckerResultID;

        $notifierMock = $this->createMock(CodeCheckerResultNotifier::class);
        $notifierMock->expects($this->never())->method('sendNotifications');
        Yii::$container->set(CodeCheckerResultNotifier::class, $notifierMock);
        $persistence = new CodeCheckerResultPersistence($submission);

        $this->expectException(CodeCheckerPersistenceException::class);

        $persistence->createNewResult();

        // Check if result id has not changed
        $this->assertEquals($originalId, Submission::findOne(1)->codeCheckerResultID);
    }

    public function testTryToSaveAlreadySavedResult()
    {
        $submission = $this->tester->grabRecord(Submission::class, ['id' => 1]);
        $originalStatus = $submission->codeCheckerResult->status;
        $originalStdout = $submission->codeCheckerResult->stdout;
        $originalStderr = $submission->codeCheckerResult->stderr;

        $notifierMock = $this->createMock(CodeCheckerResultNotifier::class);
        $notifierMock->expects($this->never())->method('sendNotifications');
        Yii::$container->set(CodeCheckerResultNotifier::class, $notifierMock);

        $persistence = new CodeCheckerResultPersistence($submission);

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
        $submission = $this->tester->grabRecord(Submission::class, ['id' => 6]);

        $notifierMock = $this->createMock(CodeCheckerResultNotifier::class);
        $notifierMock->expects($this->never())->method('sendNotifications');
        Yii::$container->set(CodeCheckerResultNotifier::class, $notifierMock);

        $persistence = new CodeCheckerResultPersistence($submission);

        $this->expectException(\InvalidArgumentException::class);

        $persistence->saveResult('path_to_tar', 0, 'stdout', 'stderr');

        // Check if result has not changed
        $result = $this->tester->grabRecord(CodeCheckerResult::class, ['id' => $submission->codeCheckerResultID]);
        $this->assertEquals(CodeCheckerResult::STATUS_IN_PROGRESS, $result->status);
        $this->assertNull($result->stdout);
        $this->assertNull($result->stderr);
        $this->assertNull($result->runnerErrorMessage);
    }

    public function testTryToSaveToAFileWithoutResult()
    {
        $submission = $this->tester->grabRecord(Submission::class, ['id' => 2]);
        $persistence = new CodeCheckerResultPersistence($submission);

        $notifierMock = $this->createMock(CodeCheckerResultNotifier::class);
        $notifierMock->expects($this->never())->method('sendNotifications');
        Yii::$container->set(CodeCheckerResultNotifier::class, $notifierMock);

        $this->expectException(CodeCheckerPersistenceException::class);

        $persistence->saveResult(null, 1, 'stdout', 'stderr');
    }

    public function testSaveNoIssuesResult()
    {
        $submission = $this->tester->grabRecord(Submission::class, ['id' => 6]);

        $notifierMock = $this->createMock(CodeCheckerResultNotifier::class);
        $notifierMock->expects($this->once())->method('sendNotifications');
        Yii::$container->set(CodeCheckerResultNotifier::class, $notifierMock);

        $persistence = new CodeCheckerResultPersistence($submission);

        $persistence->saveResult(null, 0, 'stdout', '');

        $result = $this->tester->grabRecord(CodeCheckerResult::class, ['id' => $submission->codeCheckerResultID]);
        $this->assertEquals(CodeCheckerResult::STATUS_NO_ISSUES, $result->status);
        $this->assertEquals('stdout', $result->stdout);
        $this->assertEquals('', $result->stderr);
        $this->assertEmpty($result->codeCheckerReports);
        $this->assertNull($result->runnerErrorMessage);
    }

    public function testAnalysisFailedNoTarFile()
    {
        $submission = $this->tester->grabRecord(Submission::class, ['id' => 6]);

        $notifierMock = $this->createMock(CodeCheckerResultNotifier::class);
        $notifierMock->expects($this->once())->method('sendNotifications');
        Yii::$container->set(CodeCheckerResultNotifier::class, $notifierMock);

        $persistence = new CodeCheckerResultPersistence($submission);

        $persistence->saveResult(null, 1, 'stdout', 'stderr');

        $result = $this->tester->grabRecord(CodeCheckerResult::class, ['id' => $submission->codeCheckerResultID]);
        $this->assertEquals(CodeCheckerResult::STATUS_ANALYSIS_FAILED, $result->status);
        $this->assertEquals('stdout', $result->stdout);
        $this->assertEquals('stderr', $result->stderr);
        $this->assertEmpty($result->codeCheckerReports);
        $this->assertNull($result->runnerErrorMessage);
    }

    public function testAnalysisFailedNoTarFileCanvas()
    {
        $submission = $this->tester->grabRecord(Submission::class, ['id' => 6]);

        $notifierMock = $this->createMock(CodeCheckerResultNotifier::class);
        $notifierMock->expects($this->once())->method('sendNotifications');
        Yii::$container->set(CodeCheckerResultNotifier::class, $notifierMock);

        $persistence = new CodeCheckerResultPersistence($submission);

        $persistence->saveResult(null, 1, 'stdout', 'stderr');

        $result = $this->tester->grabRecord(CodeCheckerResult::class, ['id' => $submission->codeCheckerResultID]);
        $this->assertEquals(CodeCheckerResult::STATUS_ANALYSIS_FAILED, $result->status);
        $this->assertEquals('stdout', $result->stdout);
        $this->assertEquals('stderr', $result->stderr);
        $this->assertEmpty($result->codeCheckerReports);
        $this->assertNull($result->runnerErrorMessage);
    }

    public function testAnalysisFailedEmptyJsonArray()
    {
        $submission = $this->tester->grabRecord(Submission::class, ['id' => 6]);

        $notifierMock = $this->createMock(CodeCheckerResultNotifier::class);
        $notifierMock->expects($this->once())->method('sendNotifications');
        Yii::$container->set(CodeCheckerResultNotifier::class, $notifierMock);

        $persistence = new CodeCheckerResultPersistence($submission);

        $persistence->saveResult(
            codecept_data_dir('codechecker_samples/valid_empty_json_array.tar'),
            1,
            'stdout',
            'stderr'
        );

        $result = $this->tester->grabRecord(CodeCheckerResult::class, ['id' => $submission->codeCheckerResultID]);
        $this->assertEquals(CodeCheckerResult::STATUS_ANALYSIS_FAILED, $result->status);
        $this->assertEquals('stdout', $result->stdout);
        $this->assertEquals('stderr', $result->stderr);
        $this->assertEmpty($result->codeCheckerReports);
        $this->assertNull($result->runnerErrorMessage);
    }

    public function testIssuesFoundResultWithTar()
    {
        $submission = $this->tester->grabRecord(Submission::class, ['id' => 6]);

        $notifierMock = $this->createMock(CodeCheckerResultNotifier::class);
        $notifierMock->expects($this->once())->method('sendNotifications');
        Yii::$container->set(CodeCheckerResultNotifier::class, $notifierMock);

        $persistence = new CodeCheckerResultPersistence($submission);

        $persistence->saveResult(
            codecept_data_dir('codechecker_samples/valid_cpp_reports.tar'),
            1,
            'stdout',
            'stderr'
        );

        $result = $this->tester->grabRecord(CodeCheckerResult::class, ['id' => $submission->codeCheckerResultID]);
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
        $submission = $this->tester->grabRecord(Submission::class, ['id' => 6]);

        $notifierMock = $this->createMock(CodeCheckerResultNotifier::class);
        $notifierMock->expects($this->never())->method('sendNotifications');
        Yii::$container->set(CodeCheckerResultNotifier::class, $notifierMock);

        $persistence = new CodeCheckerResultPersistence($submission);

        $this->expectException(CodeCheckerPersistenceException::class);

        $persistence->saveResult(
            codecept_data_dir('codechecker_samples/without_json.tar'),
            1,
            'stdout',
            'stderr'
        );

        // Check if result has not changed
        $result = $this->tester->grabRecord(CodeCheckerResult::class, ['id' => $submission->codeCheckerResultID]);
        $this->assertEquals(CodeCheckerResult::STATUS_IN_PROGRESS, $result->status);
        $this->assertNull($result->stdout);
        $this->assertNull($result->stderr);
        $this->assertNull($result->runnerErrorMessage);
    }

    public function testTryToSaveIssuesFoundResultWithUnsupportedJsonVersion()
    {
        $submission = $this->tester->grabRecord(Submission::class, ['id' => 6]);

        $notifierMock = $this->createMock(CodeCheckerResultNotifier::class);
        $notifierMock->expects($this->never())->method('sendNotifications');
        Yii::$container->set(CodeCheckerResultNotifier::class, $notifierMock);

        $persistence = new CodeCheckerResultPersistence($submission);

        $this->expectException(CodeCheckerPersistenceException::class);

        $persistence->saveResult(
            codecept_data_dir('codechecker_samples/json_invalid_version.tar'),
            1,
            'stdout',
            'stderr'
        );

        // Check if result has not changed
        $result = CodeCheckerResult::findOne($submission->codeCheckerResultID);
        $this->assertEquals(CodeCheckerResult::STATUS_IN_PROGRESS, $result->status);
        $this->assertNull($result->stdout);
        $this->assertNull($result->stderr);
        $this->assertNull($result->runnerErrorMessage);
    }

    public function testTryToSaveIssuesFoundResultWithoutHtmlReports()
    {

        $submission = $this->tester->grabRecord(Submission::class, ['id' => 6]);

        $notifierMock = $this->createMock(CodeCheckerResultNotifier::class);
        $notifierMock->expects($this->never())->method('sendNotifications');
        Yii::$container->set(CodeCheckerResultNotifier::class, $notifierMock);

        $persistence = new CodeCheckerResultPersistence($submission);

        $this->expectException(CodeCheckerPersistenceException::class);
        $persistence->saveResult(
            codecept_data_dir('codechecker_samples/without_html.tar'),
            1,
            'stdout',
            'stderr'
        );

        // Check if result has not changed
        $result = CodeCheckerResult::findOne($submission->codeCheckerResultID);
        $this->assertEquals(CodeCheckerResult::STATUS_IN_PROGRESS, $result->status);
        $this->assertNull($result->stdout);
        $this->assertNull($result->stderr);
        $this->assertNull($result->runnerErrorMessage);
    }

    public function testSaveWithRunnerFailedStatus()
    {
        $submission = $this->tester->grabRecord(Submission::class, ['id' => 6]);

        $notifierMock = $this->createMock(CodeCheckerResultNotifier::class);
        $notifierMock->expects($this->once())->method('sendNotifications');
        Yii::$container->set(CodeCheckerResultNotifier::class, $notifierMock);

        $persistence = new CodeCheckerResultPersistence($submission);
        $persistence->saveRunnerError("Run failed");

        // Check if result has not changed
        $result = CodeCheckerResult::findOne($submission->codeCheckerResultID);
        $this->assertEquals(CodeCheckerResult::STATUS_RUNNER_ERROR, $result->status);
        $this->assertNull($result->stdout);
        $this->assertNull($result->stderr);
        $this->assertEquals("Run failed", $result->runnerErrorMessage);
    }

    public function testTryToSaveWithRunnerErrorWithoutResult()
    {
        $submission = $this->tester->grabRecord(Submission::class, ['id' => 2]);

        $notifierMock = $this->createMock(CodeCheckerResultNotifier::class);
        $notifierMock->expects($this->never())->method('sendNotifications');
        Yii::$container->set(CodeCheckerResultNotifier::class, $notifierMock);

        $persistence = new CodeCheckerResultPersistence($submission);

        $this->expectException(CodeCheckerPersistenceException::class);

        $persistence->saveRunnerError("Run failed");
    }

    public function testTryToSaveWithRunnerErrorAlreadySaved()
    {
        $submission = $this->tester->grabRecord(Submission::class, ['id' => 1]);

        $notifierMock = $this->createMock(CodeCheckerResultNotifier::class);
        $notifierMock->expects($this->never())->method('sendNotifications');
        Yii::$container->set(CodeCheckerResultNotifier::class, $notifierMock);

        $persistence = new CodeCheckerResultPersistence($submission);
        $originalStatus = $submission->codeCheckerResult->status;
        $originalRunnerErrorMessage = $submission->codeCheckerResult->runnerErrorMessage;

        $this->expectException(CodeCheckerPersistenceException::class);

        $persistence->saveResult(null, 1, 'stdout', 'stderr');

        // Check if result has not changed
        $result = CodeCheckerResult::findOne("1-result1");
        $this->assertEquals($originalStatus, $result->status);
        $this->assertEquals($originalRunnerErrorMessage, $result->runnerErrorMessage);
        $this->assertNull($result->runnerErrorMessage);
    }
}
