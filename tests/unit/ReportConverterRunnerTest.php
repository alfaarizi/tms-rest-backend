<?php

namespace app\tests\unit;

use app\components\codechecker\ReportConverterRunner;
use app\components\docker\DockerContainer;
use app\components\docker\DockerImageManager;
use app\models\Submission;
use app\tests\unit\fixtures\CodeCheckerResultFixture;
use app\tests\unit\fixtures\TaskFilesFixture;
use app\tests\unit\fixtures\SubmissionsFixture;
use app\tests\unit\fixtures\TaskFixture;
use Codeception\Test\Unit;
use UnitTester;
use yii\helpers\FileHelper;
use Yii;

class ReportConverterRunnerTest extends Unit
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
     * Create ReportConverterRunner.
     * Replace the buildAndStartAnalyzerContainer and buildAndStartReportConverterContainer methods to inject mock containers.
     * @return void
     */
    private function initRunner()
    {
        $this->runner = $this->getMockBuilder(ReportConverterRunner::class)
            ->enableOriginalConstructor()
            ->setConstructorArgs([$this->submission])
            ->onlyMethods(['buildAndStartAnalyzerContainer', 'buildAndStartReportConverterContainer'])
            ->disableArgumentCloning()
            ->getMock();
    }

    protected function _before()
    {
        $this->submission = $this->tester->grabRecord(Submission::class, ['id' => 5]);
        $this->submission->task->testOS = 'linux';
        $this->submission->task->imageName = 'imageName:latest';
        $this->submission->task->staticCodeAnalyzerTool = 'roslynator';
        $this->submission->task->staticCodeAnalyzerInstructions = 'roslynator analyze';
        $this->tester->copyDir(codecept_data_dir("appdata_samples"), Yii::getAlias("@appdata"));

        $dockerImageManagerMock = $this->createMock(DockerImageManager::class);
        $dockerImageManagerMock->method('alreadyBuilt')->willReturnOnConsecutiveCalls(true, true);
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
     * @testWith ["linux"]
     *           ["windows"]
     */
    public function testWorkDirContentsWithSkipfile(string $os)
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

        $testDir = $this->getAndTestTmpPath() . '/test';
        $this->assertDirectoryExists($testDir . '/submission');

        $this->assertStringEqualsFile(
            $testDir . '/skipfile',
            $this->submission->task->codeCheckerSkipFile
        );
        $this->assertFileEquals(
            codecept_data_dir('appdata_samples/uploadedfiles/5007/file2.txt'),
            $testDir . '/test_files/file2.txt'
        );
        $this->assertFileEquals(
            codecept_data_dir('appdata_samples/uploadedfiles/5007/file3.txt'),
            $testDir . '/test_files/file3.txt'
        );
    }

    /**
     * @testWith ["linux"]
     *           ["windows"]
     */
    public function testWorkDirContentsWithoutSkipfile(string $os)
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

        $testDir = $this->getAndTestTmpPath() . '/test';
        $this->assertDirectoryExists($testDir . '/submission');

        $this->assertFileNotExists($testDir . '/skipfile');
        $this->assertFileEquals(
            codecept_data_dir('appdata_samples/uploadedfiles/5007/file2.txt'),
            $testDir . '/test_files/file2.txt'
        );
        $this->assertFileEquals(
            codecept_data_dir('appdata_samples/uploadedfiles/5007/file3.txt'),
            $testDir . '/test_files/file3.txt'
        );
    }

    /**
     * @testWith ["linux", "analyze.sh"]
     *           ["windows", "analyze.ps1"]
     */
    public function testWorkDirContentsAnalyzeScript(string $os, string $scriptName)
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

        $tmpFolder = $this->getAndTestTmpPath() . '/test';
        $this->assertStringEqualsFile(
            $tmpFolder . '/' . $scriptName,
            "roslynator analyze"
        );
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

    public function testPassedRunLinux()
    {
        $this->submission->task->testOS = "linux";

        $analyzerContainerMock = $this->createMock(DockerContainer::class);
        $analyzerContainerMock
            ->expects($this->once())
            ->method('executeCommand')
            ->with(
                [
                   'timeout',
                   Yii::$app->params['evaluator']['staticAnalysisTimeout'],
                   '/bin/bash',
                   '/test/analyze.sh',
                ]
            )
            ->willReturn(
                [
                    'exitCode' => 0,
                    'stdout' => 'stdout sample',
                    'stderr' => '',
                ]
            );
        $analyzerContainerMock->expects($this->once())->method('uploadArchive');
        $analyzerContainerMock->expects($this->once())->method('stopContainer');
        $this->runner->expects($this->once())->method('buildAndStartAnalyzerContainer')->willReturn($analyzerContainerMock);
        $this->runner->expects($this->never())->method('buildAndStartReportConverterContainer');

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
        $this->runner->expects($this->never())->method('buildAndStartReportConverterContainer');

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

        $analyzerContainerMock
            ->method('executeCommand')
            ->withConsecutive(
                [
                    [
                        'timeout',
                        Yii::$app->params['evaluator']['staticAnalysisTimeout'],
                        '/bin/bash',
                        '/test/analyze.sh'
                    ]
                ],
                [
                    ['test', '-e', '/test/roslynator.xml']
                ]
            )
            ->willReturnOnConsecutiveCalls(
                [
                    'exitCode' => 1,
                    'stdout' => 'stdout sample',
                    'stderr' => 'stderr sample',
                ],
                [
                    'exitCode' => 0,
                    'stdout' => 'stdout',
                    'stderr' => '',
                ]
            );

        $analyzerContainerMock->expects($this->once())->method('uploadArchive');
        $analyzerContainerMock->expects($this->once())->method('stopContainer');
        $analyzerContainerMock->expects($this->once())->method('downloadArchive');
        $this->runner->expects($this->once())->method('buildAndStartAnalyzerContainer')->willReturn($analyzerContainerMock);

        $reportConverterContainerMock = $this->createMock(DockerContainer::class);
        $reportConverterContainerMock
            ->method('executeCommand')
            ->withConsecutive(
                [
                    [
                        'report-converter',
                        '-t', 'roslynator',
                        '-o', '/test/reports/plist',
                        '/test/roslynator.xml'
                    ]
                ],
                [
                    [
                        "CodeChecker", "parse", "/test/reports/plist",
                        "--export", "json",
                        "--output", "/test/reports/reports.json",
                        "--trim-path-prefix", "/test/submission",
                    ]
                ],
                [
                    [
                        "CodeChecker", "parse", "/test/reports/plist",
                        "--export", "html",
                        "--output", "/test/reports/html",
                        "--trim-path-prefix", "/test/submission",
                    ]
                ]
            )
            ->willReturnOnConsecutiveCalls(
                [
                    'exitCode' => 0,
                    'stdout' => 'stdout',
                    'stderr' => '',
                ],
                [
                    'exitCode' => 2,
                    'stdout' => 'stdout',
                    'stderr' => 'stderr',
                ],
                [
                    'exitCode' => 2,
                    'stdout' => 'stdout',
                    'stderr' => 'stderr',
                ],
            );

        $reportConverterContainerMock->expects($this->once())->method('uploadArchive');
        $reportConverterContainerMock->expects($this->once())->method('downloadArchive');

        $this->runner
            ->expects($this->once())
            ->method('buildAndStartReportConverterContainer')->willReturn($reportConverterContainerMock);

        $result = $this->runner->run();

        $this->assertEquals(1, $result['exitCode']);
        $this->assertEquals('stdout sample', $result['stdout']);
        $this->assertEquals('stderr sample', $result['stderr']);
        $this->assertNotNull($result['tarPath']);
    }

    public function testFailedLinuxWithSkipfile()
    {
        $this->submission->task->testOS = "linux";
        $this->submission->task->codeCheckerSkipFile = "- */ignored.cs";
        $analyzerContainerMock = $this->createMock(DockerContainer::class);

        $analyzerContainerMock
            ->method('executeCommand')
            ->withConsecutive(
                [
                    [
                        'timeout',
                        Yii::$app->params['evaluator']['staticAnalysisTimeout'],
                        '/bin/bash',
                        '/test/analyze.sh'
                    ]
                ],
                [
                    ['test', '-e', '/test/roslynator.xml']
                ]
            )
            ->willReturnOnConsecutiveCalls(
                [
                    'exitCode' => 1,
                    'stdout' => 'stdout sample',
                    'stderr' => 'stderr sample',
                ],
                [
                    'exitCode' => 0,
                    'stdout' => 'stdout',
                    'stderr' => '',
                ]
            );

        $analyzerContainerMock->expects($this->once())->method('uploadArchive');
        $analyzerContainerMock->expects($this->once())->method('stopContainer');
        $analyzerContainerMock->expects($this->once())->method('downloadArchive');
        $this->runner->expects($this->once())->method('buildAndStartAnalyzerContainer')->willReturn($analyzerContainerMock);

        $reportConverterContainerMock = $this->createMock(DockerContainer::class);
        $reportConverterContainerMock
            ->method('executeCommand')
            ->withConsecutive(
                [
                    [
                        'report-converter',
                        '-t', 'roslynator',
                        '-o', '/test/reports/plist',
                        '/test/roslynator.xml'
                    ]
                ],
                [
                    [
                        "CodeChecker", "parse", "/test/reports/plist",
                        "--export", "json",
                        "--output", "/test/reports/reports.json",
                        "--trim-path-prefix", "/test/submission",
                        "--ignore", "/test/skipfile",
                    ]
                ],
                [
                    [
                        "CodeChecker", "parse", "/test/reports/plist",
                        "--export", "html",
                        "--output", "/test/reports/html",
                        "--trim-path-prefix", "/test/submission",
                        "--ignore", "/test/skipfile",
                    ]
                ]
            )
            ->willReturnOnConsecutiveCalls(
                [
                    'exitCode' => 0,
                    'stdout' => 'stdout',
                    'stderr' => '',
                ],
                [
                    'exitCode' => 2,
                    'stdout' => 'stdout',
                    'stderr' => 'stderr',
                ],
                [
                    'exitCode' => 2,
                    'stdout' => 'stdout',
                    'stderr' => 'stderr',
                ],
            );

        $reportConverterContainerMock->expects($this->once())->method('uploadArchive');
        $reportConverterContainerMock->expects($this->once())->method('downloadArchive');

        $reportConverterContainerMock->expects($this->once())->method('uploadArchive');
        $reportConverterContainerMock->expects($this->once())->method('downloadArchive');

        $this->runner->expects($this->once())->method('buildAndStartReportConverterContainer')->willReturn($reportConverterContainerMock);
        $result = $this->runner->run();

        $this->assertEquals(1, $result['exitCode']);
        $this->assertEquals('stdout sample', $result['stdout']);
        $this->assertEquals('stderr sample', $result['stderr']);
        $this->assertNotNull($result['tarPath']);
    }

    public function testFailedLinuxWithoutReports()
    {
        $this->submission->task->testOS = "linux";
        $analyzerContainerMock = $this->createMock(DockerContainer::class);

        $analyzerContainerMock
            ->method('executeCommand')
            ->withConsecutive(
                [
                    [
                        'timeout',
                        Yii::$app->params['evaluator']['staticAnalysisTimeout'],
                        '/bin/bash',
                        '/test/analyze.sh'
                    ]
                ],
                [
                    ['test', '-e', '/test/roslynator.xml']
                ]
            )
            ->willReturnOnConsecutiveCalls(
                [
                    'exitCode' => 1,
                    'stdout' => 'stdout sample',
                    'stderr' => 'stderr sample',
                ],
                [
                    'exitCode' => 1,
                    'stdout' => 'stdout',
                    'stderr' => '',
                ]
            );

        $analyzerContainerMock->expects($this->once())->method('uploadArchive');
        $analyzerContainerMock->expects($this->once())->method('stopContainer');
        $analyzerContainerMock->expects($this->never())->method('downloadArchive');
        $this->runner->expects($this->once())->method('buildAndStartAnalyzerContainer')->willReturn($analyzerContainerMock);
        $this->runner->expects($this->never())->method('buildAndStartReportConverterContainer');

        $result = $this->runner->run();

        $this->assertEquals(1, $result['exitCode']);
        $this->assertEquals('stdout sample', $result['stdout']);
        $this->assertEquals('stderr sample', $result['stderr']);
        $this->assertNull($result['tarPath']);
    }

    public function testFailedWindows()
    {
        $this->submission->task->testOS = "windows";
        $analyzerContainerMock = $this->createMock(DockerContainer::class);

        $analyzerContainerMock
            ->method('executeCommand')
            ->withConsecutive(
                [
                    ["powershell", "C:\\test\\analyze.ps1"]
                ],
                [
                    ["powershell", "-Command", "Test-Path -Path C:\\test\\roslynator.xml"]
                ]
            )
            ->willReturnOnConsecutiveCalls(
                [
                    'exitCode' => 1,
                    'stdout' => 'stdout sample',
                    'stderr' => 'stderr sample',
                ],
                [
                    'exitCode' => 0,
                    'stdout' => 'True',
                    'stderr' => '',
                ]
            );

        $analyzerContainerMock->expects($this->once())->method('uploadArchive');
        $analyzerContainerMock->expects($this->once())->method('stopContainer');
        $analyzerContainerMock->expects($this->once())->method('downloadArchive');
        $this->runner->expects($this->once())->method('buildAndStartAnalyzerContainer')->willReturn($analyzerContainerMock);

        $reportConverterContainerMock = $this->createMock(DockerContainer::class);
        $reportConverterContainerMock
            ->method('executeCommand')
            ->withConsecutive(
                [
                    [
                        'report-converter',
                        '-t', 'roslynator',
                        '-o', 'C:\\test\\reports\\plist',
                        'C:\\test\\roslynator.xml'
                    ]
                ],
                [
                    [
                        "CodeChecker", "parse", "C:\\test\\reports\\plist",
                        "--export", "json",
                        "--output", "C:\\test\\reports\\reports.json",
                        "--trim-path-prefix", "C:\\test\\submission",
                    ]
                ],
                [
                    [
                        "CodeChecker", "parse", "C:\\test\\reports\\plist",
                        "--export", "html",
                        "--output", "C:\\test\\reports\\html",
                        "--trim-path-prefix", "C:\\test\\submission",
                    ]
                ]
            )
            ->willReturnOnConsecutiveCalls(
                [
                    'exitCode' => 0,
                    'stdout' => 'stdout',
                    'stderr' => '',
                ],
                [
                    'exitCode' => 2,
                    'stdout' => 'stdout',
                    'stderr' => 'stderr',
                ],
                [
                    'exitCode' => 2,
                    'stdout' => 'stdout',
                    'stderr' => 'stderr',
                ],
            );

        $this->runner->expects($this->once())->method('buildAndStartReportConverterContainer')->willReturn($reportConverterContainerMock);

        $result = $this->runner->run();

        $this->assertEquals(1, $result['exitCode']);
        $this->assertEquals('stdout sample', $result['stdout']);
        $this->assertEquals('stderr sample', $result['stderr']);
        $this->assertNotNull($result['tarPath']);
    }

    public function testFailedWindowsWithSkipfile()
    {
        $this->submission->task->testOS = "windows";
        $this->submission->task->codeCheckerSkipFile = "- */ignored.cs";
        $analyzerContainerMock = $this->createMock(DockerContainer::class);

        $analyzerContainerMock
            ->method('executeCommand')
            ->withConsecutive(
                [
                    ["powershell", "C:\\test\\analyze.ps1"]
                ],
                [
                    ["powershell", "-Command", "Test-Path -Path C:\\test\\roslynator.xml"]
                ]
            )
            ->willReturnOnConsecutiveCalls(
                [
                    'exitCode' => 1,
                    'stdout' => 'stdout sample',
                    'stderr' => 'stderr sample',
                ],
                [
                    'exitCode' => 0,
                    'stdout' => 'True',
                    'stderr' => '',
                ]
            );

        $analyzerContainerMock->expects($this->once())->method('uploadArchive');
        $analyzerContainerMock->expects($this->once())->method('stopContainer');
        $analyzerContainerMock->expects($this->once())->method('downloadArchive');
        $this->runner->expects($this->once())->method('buildAndStartAnalyzerContainer')->willReturn($analyzerContainerMock);

        $reportConverterContainerMock = $this->createMock(DockerContainer::class);
        $reportConverterContainerMock
            ->method('executeCommand')
            ->withConsecutive(
                [
                    [
                        'report-converter',
                        '-t', 'roslynator',
                        '-o', 'C:\\test\\reports\\plist',
                        'C:\\test\\roslynator.xml'
                    ]
                ],
                [
                    [
                        "CodeChecker", "parse", "C:\\test\\reports\\plist",
                        "--export", "json",
                        "--output", "C:\\test\\reports\\reports.json",
                        "--trim-path-prefix", "C:\\test\\submission",
                        "--ignore", "C:\\test\\skipfile"
                    ]
                ],
                [
                    [
                        "CodeChecker", "parse", "C:\\test\\reports\\plist",
                        "--export", "html",
                        "--output", "C:\\test\\reports\\html",
                        "--trim-path-prefix", "C:\\test\\submission",
                        "--ignore", "C:\\test\\skipfile"
                    ]
                ]
            )
            ->willReturnOnConsecutiveCalls(
                [
                    'exitCode' => 0,
                    'stdout' => 'stdout',
                    'stderr' => '',
                ],
                [
                    'exitCode' => 2,
                    'stdout' => 'stdout',
                    'stderr' => 'stderr',
                ],
                [
                    'exitCode' => 2,
                    'stdout' => 'stdout',
                    'stderr' => 'stderr',
                ],
            );

        $this->runner->expects($this->once())->method('buildAndStartReportConverterContainer')->willReturn($reportConverterContainerMock);

        $result = $this->runner->run();

        $this->assertEquals(1, $result['exitCode']);
        $this->assertEquals('stdout sample', $result['stdout']);
        $this->assertEquals('stderr sample', $result['stderr']);
        $this->assertNotNull($result['tarPath']);
    }

    public function testFailedWindowsWithoutReports()
    {
        $this->submission->task->testOS = "windows";
        $analyzerContainerMock = $this->createMock(DockerContainer::class);

        $analyzerContainerMock
            ->method('executeCommand')
            ->withConsecutive(
                [
                    ["powershell", "C:\\test\\analyze.ps1"]
                ],
                [
                    ["powershell", "-Command", "Test-Path -Path C:\\test\\roslynator.xml"]
                ]
            )
            ->willReturnOnConsecutiveCalls(
                [
                    'exitCode' => 1,
                    'stdout' => 'stdout sample',
                    'stderr' => 'stderr sample',
                ],
                [
                    'exitCode' => 0,
                    'stdout' => 'False',
                    'stderr' => '',
                ]
            );

        $analyzerContainerMock->expects($this->once())->method('uploadArchive');
        $analyzerContainerMock->expects($this->once())->method('stopContainer');
        $analyzerContainerMock->expects($this->never())->method('downloadArchive');
        $this->runner->expects($this->once())->method('buildAndStartAnalyzerContainer')->willReturn($analyzerContainerMock);

        $this->runner->expects($this->never())->method('buildAndStartReportConverterContainer');

        $result = $this->runner->run();

        $this->assertEquals(1, $result['exitCode']);
        $this->assertEquals('stdout sample', $result['stdout']);
        $this->assertEquals('stderr sample', $result['stderr']);
        $this->assertNull($result['tarPath']);
    }
}
