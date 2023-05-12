<?php

namespace unit;

use app\components\codechecker\ReportConverterRunner;
use app\components\docker\DockerContainer;
use app\components\docker\DockerImageManager;
use app\exceptions\CodeCheckerRunnerException;
use app\models\StudentFile;
use app\tests\unit\fixtures\CodeCheckerResultFixture;
use app\tests\unit\fixtures\InstructorFilesFixture;
use app\tests\unit\fixtures\StudentFilesFixture;
use app\tests\unit\fixtures\TaskFixture;
use Codeception\Test\Unit;
use UnitTester;
use yii\helpers\FileHelper;
use Yii;

class ReportConverterRunnerTest extends Unit
{
    protected UnitTester $tester;
    private $runner;

    public function _fixtures(): array
    {
        return [
            'tasks' => [
                'class' => TaskFixture::class,
            ],
            'studentfiles' => [
                'class' => StudentFilesFixture::class,
            ],
            'instructorfiles' => [
                'class' => InstructorFilesFixture::class,
            ],
            'codecheckerresults' => [
                'class' => CodeCheckerResultFixture::class
            ]
        ];
    }

    /**
     * Create ReportConverterRunner.
     * Replace the buildAnalyzerContainer and buildReportConverterContainer methods to inject mock containers.
     * @return void
     */
    private function initRunner()
    {
        $this->runner = $this->getMockBuilder(ReportConverterRunner::class)
            ->enableOriginalConstructor()
            ->setConstructorArgs([$this->studentFile])
            ->onlyMethods(['buildAnalyzerContainer', 'buildReportConverterContainer'])
            ->disableArgumentCloning()
            ->getMock();
    }

    protected function _before()
    {
        $this->studentFile = $this->tester->grabRecord(StudentFile::class, ['id' => 5]);
        $this->studentFile->task->imageName = 'imageName:latest';
        $this->studentFile->task->staticCodeAnalyzerTool = 'roslynator';
        $this->studentFile->task->staticCodeAnalyzerInstructions = 'roslynator analyze';
        $this->tester->copyDir(codecept_data_dir("appdata_samples"), Yii::$app->params['data_dir']);

        $dockerImageManagerMock = $this->createMock(DockerImageManager::class);
        $dockerImageManagerMock->method('alreadyBuilt')->willReturnOnConsecutiveCalls(true, true);
        Yii::$container->set(DockerImageManager::class, $dockerImageManagerMock);

        $this->initRunner();
    }

    protected function _after()
    {
        $this->tester->deleteDir(Yii::$app->params['data_dir']);
    }

    private function getAndTestTmpPath(): string
    {
        $tmpFolderList = FileHelper::findDirectories(
            Yii::$app->basePath . '/' . Yii::$app->params['data_dir'] . '/tmp/codechecker',
            ['recursive' => false]
        );
        $this->assertEquals(1, count($tmpFolderList), "{data_dir}/tmp/codechecker shouldn't be empty");
        return $tmpFolderList[0];
    }

    public function testEvaluatorImageIsNotAvailable()
    {
        $dockerImageManagerMock = $this->createMock(DockerImageManager::class);
        $dockerImageManagerMock->method('alreadyBuilt')->willReturnOnConsecutiveCalls(false, true);
        Yii::$container->set(DockerImageManager::class, $dockerImageManagerMock);
        $this->initRunner();

        $this->expectException(CodeCheckerRunnerException::class);

        $this->runner->run();
    }

    public function testReportConverterImageIsNotAvailable()
    {
        $dockerImageManagerMock = $this->createMock(DockerImageManager::class);
        $dockerImageManagerMock->method('alreadyBuilt')->willReturnOnConsecutiveCalls(true, false);
        Yii::$container->set(DockerImageManager::class, $dockerImageManagerMock);
        $this->initRunner();

        $this->expectException(CodeCheckerRunnerException::class);

        $this->runner->run();
    }

    /**
     * @testWith ["linux"]
     *           ["windows"]
     */
    public function testWorkDirContentsWithSkipfile(string $os)
    {
        $this->studentFile->task->testOS = $os;
        $this->studentFile->task->codeCheckerSkipFile = "- */skipped.cpp";

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
        $this->runner->expects($this->once())->method('buildAnalyzerContainer')->willReturn($analyzerContainerMock);

        $this->runner->run();

        $testDir = $this->getAndTestTmpPath() . '/test';
        $this->assertDirectoryExists($testDir . '/submission');

        $this->assertStringEqualsFile(
            $testDir . '/skipfile',
            $this->studentFile->task->codeCheckerSkipFile
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
        $this->studentFile->task->testOS = $os;
        $this->studentFile->task->codeCheckerSkipFile = null;

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
        $this->runner->expects($this->once())->method('buildAnalyzerContainer')->willReturn($analyzerContainerMock);

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
        $this->studentFile->task->testOS = $os;
        $this->studentFile->task->codeCheckerSkipFile = null;

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
        $this->runner->expects($this->once())->method('buildAnalyzerContainer')->willReturn($analyzerContainerMock);

        $this->runner->run();

        $tmpFolder = $this->getAndTestTmpPath() . '/test';
        $this->assertStringEqualsFile(
            $tmpFolder . '/' . $scriptName,
            "roslynator analyze"
        );
    }

    public function testDeleteSolution()
    {
        $this->studentFile->task->codeCheckerSkipFile = "- */skipped.cpp";

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
        $this->runner->expects($this->once())->method('buildAnalyzerContainer')->willReturn($analyzerContainerMock);

        $this->runner->run();
        $this->runner->deleteWorkDirectory();

        $tmpFolderList = scandir(Yii::$app->basePath . '/' . Yii::$app->params['data_dir'] . '/tmp/codechecker');
        $this->assertEquals(2, count($tmpFolderList), "{data_dir}/tmp/codechecker should be empty");
    }

    public function testPassedRunLinux()
    {
        $this->studentFile->task->testOS = "linux";

        $analyzerContainerMock = $this->createMock(DockerContainer::class);
        $analyzerContainerMock->expects($this->once())->method('startContainer');
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
        $this->runner->expects($this->once())->method('buildAnalyzerContainer')->willReturn($analyzerContainerMock);
        $this->runner->expects($this->never())->method('buildReportConverterContainer');

        $result = $this->runner->run();

        $this->assertEquals(0, $result['exitCode']);
        $this->assertEquals('stdout sample', $result['stdout']);
        $this->assertEmpty($result['stderr']);
        $this->assertNull($result['tarPath']);
    }

    public function testPassedRunWindows()
    {
        $this->studentFile->task->testOS = "windows";

        $analyzerContainerMock = $this->createMock(DockerContainer::class);
        $analyzerContainerMock->expects($this->once())->method('startContainer');
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
        $this->runner->expects($this->once())->method('buildAnalyzerContainer')->willReturn($analyzerContainerMock);
        $this->runner->expects($this->never())->method('buildReportConverterContainer');

        $result = $this->runner->run();

        $this->assertEquals(0, $result['exitCode']);
        $this->assertEquals('stdout sample', $result['stdout']);
        $this->assertEquals('stderr sample', $result['stderr']);
        $this->assertNull($result['tarPath']);
    }

    public function testFailedLinux()
    {
        $this->studentFile->task->testOS = "linux";
        $analyzerContainerMock = $this->createMock(DockerContainer::class);
        $analyzerContainerMock->expects($this->once())->method('startContainer');

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
        $this->runner->expects($this->once())->method('buildAnalyzerContainer')->willReturn($analyzerContainerMock);

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
            ->method('buildReportConverterContainer')->willReturn($reportConverterContainerMock);

        $result = $this->runner->run();

        $this->assertEquals(1, $result['exitCode']);
        $this->assertEquals('stdout sample', $result['stdout']);
        $this->assertEquals('stderr sample', $result['stderr']);
        $this->assertNotNull($result['tarPath']);
    }

    public function testFailedLinuxWithSkipfile()
    {
        $this->studentFile->task->testOS = "linux";
        $this->studentFile->task->codeCheckerSkipFile = "- */ignored.cs";
        $analyzerContainerMock = $this->createMock(DockerContainer::class);
        $analyzerContainerMock->expects($this->once())->method('startContainer');

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
        $this->runner->expects($this->once())->method('buildAnalyzerContainer')->willReturn($analyzerContainerMock);

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

        $this->runner->expects($this->once())->method('buildReportConverterContainer')->willReturn($reportConverterContainerMock);
        $result = $this->runner->run();

        $this->assertEquals(1, $result['exitCode']);
        $this->assertEquals('stdout sample', $result['stdout']);
        $this->assertEquals('stderr sample', $result['stderr']);
        $this->assertNotNull($result['tarPath']);
    }

    public function testFailedLinuxWithoutReports()
    {
        $this->studentFile->task->testOS = "linux";
        $analyzerContainerMock = $this->createMock(DockerContainer::class);
        $analyzerContainerMock->expects($this->once())->method('startContainer');

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
        $this->runner->expects($this->once())->method('buildAnalyzerContainer')->willReturn($analyzerContainerMock);
        $this->runner->expects($this->never())->method('buildReportConverterContainer');

        $result = $this->runner->run();

        $this->assertEquals(1, $result['exitCode']);
        $this->assertEquals('stdout sample', $result['stdout']);
        $this->assertEquals('stderr sample', $result['stderr']);
        $this->assertNull($result['tarPath']);
    }

    public function testFailedWindows()
    {
        $this->studentFile->task->testOS = "windows";
        $analyzerContainerMock = $this->createMock(DockerContainer::class);
        $analyzerContainerMock->expects($this->once())->method('startContainer');

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
        $this->runner->expects($this->once())->method('buildAnalyzerContainer')->willReturn($analyzerContainerMock);

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

        $this->runner->expects($this->once())->method('buildReportConverterContainer')->willReturn($reportConverterContainerMock);

        $result = $this->runner->run();

        $this->assertEquals(1, $result['exitCode']);
        $this->assertEquals('stdout sample', $result['stdout']);
        $this->assertEquals('stderr sample', $result['stderr']);
        $this->assertNotNull($result['tarPath']);
    }

    public function testFailedWindowsWithSkipfile()
    {
        $this->studentFile->task->testOS = "windows";
        $this->studentFile->task->codeCheckerSkipFile = "- */ignored.cs";
        $analyzerContainerMock = $this->createMock(DockerContainer::class);
        $analyzerContainerMock->expects($this->once())->method('startContainer');

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
        $this->runner->expects($this->once())->method('buildAnalyzerContainer')->willReturn($analyzerContainerMock);

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

        $this->runner->expects($this->once())->method('buildReportConverterContainer')->willReturn($reportConverterContainerMock);

        $result = $this->runner->run();

        $this->assertEquals(1, $result['exitCode']);
        $this->assertEquals('stdout sample', $result['stdout']);
        $this->assertEquals('stderr sample', $result['stderr']);
        $this->assertNotNull($result['tarPath']);
    }

    public function testFailedWindowsWithoutReports()
    {
        $this->studentFile->task->testOS = "windows";
        $analyzerContainerMock = $this->createMock(DockerContainer::class);
        $analyzerContainerMock->expects($this->once())->method('startContainer');

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
        $this->runner->expects($this->once())->method('buildAnalyzerContainer')->willReturn($analyzerContainerMock);

        $this->runner->expects($this->never())->method('buildReportConverterContainer');

        $result = $this->runner->run();

        $this->assertEquals(1, $result['exitCode']);
        $this->assertEquals('stdout sample', $result['stdout']);
        $this->assertEquals('stderr sample', $result['stderr']);
        $this->assertNull($result['tarPath']);
    }
}
