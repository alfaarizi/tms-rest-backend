<?php

namespace app\tests\unit;

use app\components\codechecker\CodeCheckerRunner;
use app\components\docker\DockerContainer;
use app\components\docker\DockerImageManager;
use app\exceptions\CodeCheckerRunnerException;
use app\models\Submission;
use app\tests\unit\fixtures\CodeCheckerResultFixture;
use app\tests\unit\fixtures\TaskFilesFixture;
use app\tests\unit\fixtures\SubmissionsFixture;
use app\tests\unit\fixtures\TaskFixture;
use Codeception\Test\Unit;
use UnitTester;
use Yii;
use yii\helpers\FileHelper;

class CodeCheckerRunnerTest extends Unit
{
    protected UnitTester $tester;
    private Submission $submission;
    private $runner;

    public function _fixtures(): array
    {
        return [
            'tasks' => [
                'class' => TaskFixture::class,
            ],
            'submission' => [
                'class' => SubmissionsFixture::class,
            ],
            'taskfiles' => [
                'class' => TaskFilesFixture::class,
            ],
            'codecheckerresults' => [
                'class' => CodeCheckerResultFixture::class
            ]
        ];
    }

    /**
     * Create CodeCheckerRunner.
     * Replace the buildAndStartAnalyzerContainer method to inject mock containers.
     * @return void
     */
    private function initRunner()
    {
        $this->runner = $this->getMockBuilder(CodeCheckerRunner::class)
            ->enableOriginalConstructor()
            ->setConstructorArgs([$this->submission])
            ->onlyMethods(['buildAndStartAnalyzerContainer'])
            ->disableArgumentCloning()
            ->getMock();
    }

    protected function _before()
    {
        $this->submission = $this->tester->grabRecord(Submission::class, ['id' => 5]);
        $this->submission->task->imageName = 'imageName:latest';
        $this->submission->task->staticCodeAnalyzerTool = 'codechecker';
        $this->submission->task->codeCheckerCompileInstructions = 'g++ *.cpp';
        $this->tester->copyDir(codecept_data_dir("appdata_samples"), Yii::getAlias("@appdata"));

        $dockerImageManagerMock = $this->createMock(DockerImageManager::class);
        $dockerImageManagerMock->method('alreadyBuilt')->willReturnOnConsecutiveCalls(true);
        Yii::$container->set(DockerImageManager::class, $dockerImageManagerMock);

        $this->initRunner();
    }

    protected function _after()
    {
        $this->tester->deleteDir(Yii::getAlias("@appdata"));
        $this->tester->deleteDir(Yii::getAlias("@tmp"));
    }

    private function getAndTestTmpPath(): string
    {
        $tmpFolderList = FileHelper::findDirectories(
            Yii::getAlias("@tmp/codechecker"),
            ['recursive' => false]
        );
        $this->assertEquals(1, count($tmpFolderList), "@tmp/codechecker shouldn't be empty");
        return $tmpFolderList[0];
    }

    /**
     * @testWith ["linux", "build.sh"]
     *           ["windows", "build.ps1"]
     */
    public function testWorkDirContentsWithSkipfile(string $os, string $expectedBuildScriptName)
    {
        $this->submission->task->testOS = $os;
        $this->submission->task->codeCheckerSkipFile = "- */skipped.cpp";

        $analyzerContainerMock = $this->createMock(DockerContainer::class);
        $analyzerContainerMock
            ->expects($this->once())
            ->method('executeCommand')
            ->willReturn(
                [
                    'exitCode' => 0,
                    'stdout' => 'stdout',
                    'stderr' => null,
                ]
            );
        $this->runner->expects($this->once())->method('buildAndStartAnalyzerContainer')->willReturn($analyzerContainerMock);

        $this->runner->run();

        $testFolder = $this->getAndTestTmpPath() . '/test';
        $this->assertDirectoryExists($testFolder . '/submission');

        $this->assertStringEqualsFile(
            $testFolder . '/' . $expectedBuildScriptName,
            $this->submission->task->codeCheckerCompileInstructions
        );
        $this->assertStringEqualsFile(
            $testFolder . '/skipfile',
            $this->submission->task->codeCheckerSkipFile
        );
        $this->assertFileEquals(
            codecept_data_dir('appdata_samples/uploadedfiles/5007/file2.txt'),
            $testFolder . '/test_files/file2.txt'
        );
        $this->assertFileEquals(
            codecept_data_dir('appdata_samples/uploadedfiles/5007/file3.txt'),
            $testFolder . '/test_files/file3.txt'
        );
    }

    /**
     * @testWith ["linux", "build.sh"]
     *           ["windows", "build.ps1"]
     */
    public function testWorkDirContentsWithoutSkipfile(string $os, string $expectedBuildScriptName)
    {
        $this->submission->task->testOS = $os;

        $analyzerContainerMock = $this->createMock(DockerContainer::class);
        $analyzerContainerMock
            ->expects($this->once())
            ->method('executeCommand')
            ->willReturn(
                [
                    'exitCode' => 0,
                    'stdout' => 'stdout',
                    'stderr' => null,
                ]
            );
        $this->runner->expects($this->once())->method('buildAndStartAnalyzerContainer')->willReturn($analyzerContainerMock);

        $this->runner->run();

        $testFolder = $this->getAndTestTmpPath() . '/test';
        $this->assertDirectoryExists($testFolder . '/submission');

        $this->assertFileNotExists($testFolder . '/skipfile');
        $this->assertStringEqualsFile(
            $testFolder . '/' . $expectedBuildScriptName,
            $this->submission->task->codeCheckerCompileInstructions
        );
        $this->assertFileEquals(
            codecept_data_dir('appdata_samples/uploadedfiles/5007/file2.txt'),
            $testFolder . '/test_files/file2.txt'
        );
        $this->assertFileEquals(
            codecept_data_dir('appdata_samples/uploadedfiles/5007/file3.txt'),
            $testFolder . '/test_files/file3.txt'
        );
    }

    /**
     * @testWith ["linux", "- *\/skipped.cpp", "", "analyze.sh", "CodeChecker check --build \"bash /test/build.sh\" --output /test/reports/plist --ignore /test/skipfile"]
     *           ["linux", "", "--enabled test", "analyze.sh", "CodeChecker check --build \"bash /test/build.sh\" --output /test/reports/plist --enabled test"]
     *           ["linux", "- *\/skipped.cpp", "--enabled test", "analyze.sh", "CodeChecker check --build \"bash /test/build.sh\" --output /test/reports/plist --ignore /test/skipfile --enabled test"]
     *           ["windows", "- *\/skipped.cpp", "", "analyze.ps1", "CodeChecker check --build \"powershell C:\\test\\build.ps1\" --output C:\\test\\reports\\plist --ignore C:\\test\\skipfile"]
     *           ["windows", "", "--enabled test", "analyze.ps1", "CodeChecker check --build \"powershell C:\\test\\build.ps1\" --output C:\\test\\reports\\plist --enabled test"]
     *           ["windows", "- *\/skipped.cpp", "--enabled test", "analyze.ps1", "CodeChecker check --build \"powershell C:\\test\\build.ps1\" --output C:\\test\\reports\\plist --ignore C:\\test\\skipfile --enabled test"]
     */
    public function testWorkDirContentsAnalyzeScript(string $os, ?string $skipFile, ?string $toggles, string $scriptName, string $expectedAnalyzeCommand)
    {
        $this->submission->task->testOS = $os;
        $this->submission->task->codeCheckerSkipFile = $skipFile;
        $this->submission->task->codeCheckerToggles = $toggles;

        $analyzerContainerMock = $this->createMock(DockerContainer::class);
        $analyzerContainerMock
            ->expects($this->once())
            ->method('executeCommand')
            ->willReturn(
                [
                    'exitCode' => 0,
                    'stdout' => 'stdout',
                    'stderr' => null,
                ]
            );
        $this->runner->expects($this->once())->method('buildAndStartAnalyzerContainer')->willReturn($analyzerContainerMock);

        $this->runner->run();

        $testFolder = $this->getAndTestTmpPath() . '/test';
        $this->assertStringEqualsFile(
            $testFolder . '/' . $scriptName,
            $expectedAnalyzeCommand
        );
    }

    public function testPassedRunLinux()
    {
        $this->submission->task->testOS = "linux";

        $analyzerContainerMock = $this->createMock(DockerContainer::class);
        $analyzerContainerMock
            ->expects($this->once())
            ->method('executeCommand')
            ->with([
               'timeout',
               Yii::$app->params['evaluator']['staticAnalysisTimeout'],
               '/bin/bash',
               '/test/analyze.sh',
            ])
            ->willReturn([
                 'exitCode' => 0,
                 'stdout' => 'stdout sample',
                 'stderr' => '',
             ]);
        $analyzerContainerMock->expects($this->once())->method('uploadArchive');
        $analyzerContainerMock->expects($this->once())->method('stopContainer');
        $this->runner->expects($this->once())->method('buildAndStartAnalyzerContainer')->willReturn($analyzerContainerMock);

        $result = $this->runner->run();

        $this->assertEquals(0, $result['exitCode']);
        $this->assertEquals('stdout sample', $result['stdout']);
        $this->assertEmpty($result['stderr']);
        $this->assertNull($result['tarPath']);
    }

    public function testPassedRunWindows()
    {
        $this->submission->task->testOS = "windows";

        $analyzerContainerMock = $this->createMock(DockerContainer::class);
        $analyzerContainerMock
            ->expects($this->once())
            ->method('executeCommand')
            ->with(['powershell', 'C:\\test\\analyze.ps1'])
            ->willReturn(
                [
                    'exitCode' => 0,
                    'stdout' => 'stdout sample',
                    'stderr' => 'stderr sample',
                ]
            );
        $analyzerContainerMock->expects($this->once())->method('uploadArchive');
        $analyzerContainerMock->expects($this->once())->method('stopContainer');
        $this->runner->expects($this->once())->method('buildAndStartAnalyzerContainer')->willReturn($analyzerContainerMock);

        $result = $this->runner->run();

        $this->assertEquals(0, $result['exitCode']);
        $this->assertEquals('stdout sample', $result['stdout']);
        $this->assertEquals('stderr sample', $result['stderr']);
        $this->assertNull($result['tarPath']);
    }

    public function testFailedLinux()
    {
        $this->submission->task->testOS = "linux";
        $analyzerContainerMock = $this->createMock(DockerContainer::class);

        $analyzeCommand = [
            'timeout',
            Yii::$app->params['evaluator']['staticAnalysisTimeout'],
            '/bin/bash',
            '/test/analyze.sh'
        ];
        $parseJsonCommand = [
            "CodeChecker", "parse", "/test/reports/plist",
            "--export", "json",
            "--output", "/test/reports/reports.json",
            "--trim-path-prefix", "/test/submission",
        ];
        $parseHtmlCommand = [
            "CodeChecker", "parse", "/test/reports/plist",
            "--export", "html",
            "--output", "/test/reports/html",
            "--trim-path-prefix", "/test/submission",
        ];
        $analyzerContainerMock
            ->method('executeCommand')
            ->withConsecutive([$analyzeCommand], [$parseJsonCommand], [$parseHtmlCommand])
            ->willReturnOnConsecutiveCalls(
                [
                    'exitCode' => 1,
                    'stdout' => 'stdout sample',
                    'stderr' => 'stderr sample',
                ],
                [
                    'exitCode' => 2,
                    'stdout' => 'stdout',
                    'stderr' => null,
                ],
                [
                    'exitCode' => 2,
                    'stdout' => 'stdout',
                    'stderr' => null,
                ]
            );

        $analyzerContainerMock->expects($this->once())->method('uploadArchive');
        $analyzerContainerMock->expects($this->once())->method('stopContainer');
        $analyzerContainerMock->expects($this->once())->method('downloadArchive');
        $this->runner->expects($this->once())->method('buildAndStartAnalyzerContainer')->willReturn($analyzerContainerMock);

        $result = $this->runner->run();

        $this->assertEquals(1, $result['exitCode']);
        $this->assertEquals('stdout sample', $result['stdout']);
        $this->assertEquals('stderr sample', $result['stderr']);
        $this->assertNotNull($result['tarPath']);
    }

    public function testFailedWindows()
    {
        $this->submission->task->testOS = "windows";
        $analyzerContainerMock = $this->createMock(DockerContainer::class);

        $analyzeCommand = [
            "powershell", "C:\\test\\analyze.ps1"
        ];
        $parseJsonCommand = [
            "CodeChecker", "parse", "C:\\test\\reports\\plist",
            "--export", "json",
            "--output", "C:\\test\\reports\\reports.json",
            "--trim-path-prefix", "C:\\test\\submission",
        ];
        $parseHtmlCommand = [
            "CodeChecker", "parse", "C:\\test\\reports\\plist",
            "--export", "html",
            "--output", "C:\\test\\reports\\html",
            "--trim-path-prefix", "C:\\test\\submission",
        ];

        $analyzerContainerMock
            ->method('executeCommand')
            ->withConsecutive([$analyzeCommand], [$parseJsonCommand], [$parseHtmlCommand])
            ->willReturnOnConsecutiveCalls(
                [
                    'exitCode' => 1,
                    'stdout' => 'stdout sample',
                    'stderr' => 'stderr sample',
                ],
                [
                    'exitCode' => 2,
                    'stdout' => 'stdout',
                    'stderr' => null,
                ],
                [
                    'exitCode' => 2,
                    'stdout' => 'stdout',
                    'stderr' => null,
                ]
            );

        $analyzerContainerMock->expects($this->once())->method('uploadArchive');
        $analyzerContainerMock->expects($this->once())->method('stopContainer');
        $analyzerContainerMock->expects($this->once())->method('downloadArchive');
        $this->runner->expects($this->once())->method('buildAndStartAnalyzerContainer')->willReturn($analyzerContainerMock);

        $result = $this->runner->run();

        $this->assertEquals(1, $result['exitCode']);
        $this->assertEquals('stdout sample', $result['stdout']);
        $this->assertEquals('stderr sample', $result['stderr']);
        $this->assertNotNull($result['tarPath']);
    }

    public function testDeleteSolution()
    {
        $this->submission->task->codeCheckerSkipFile = "- */skipped.cpp";

        $analyzerContainerMock = $this->createMock(DockerContainer::class);
        $analyzerContainerMock
            ->expects($this->once())
            ->method('executeCommand')
            ->willReturn(
                [
                    'exitCode' => 0,
                    'stdout' => 'stdout',
                    'stderr' => null,
                ]
            );
        $this->runner->expects($this->once())->method('buildAndStartAnalyzerContainer')->willReturn($analyzerContainerMock);

        $this->runner->run();
        $this->runner->deleteWorkDirectory();

        $tmpFolderList = scandir(Yii::getAlias("@tmp/codechecker"));
        $this->assertEquals(2, count($tmpFolderList), "@tmp/codechecker should be empty");
    }
}
