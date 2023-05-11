<?php

namespace app\components\codechecker;

use app\components\docker\DockerContainer;
use app\components\docker\DockerContainerBuilder;
use app\components\docker\DockerImageManager;
use app\exceptions\CodeCheckerRunnerException;
use app\models\InstructorFile;
use app\models\StudentFile;
use Yii;
use yii\base\BaseObject;
use yii\helpers\FileHelper;

/**
 * An abstract class that contains the steps to run static code analysis on a student file
 * and retrieves results in the CodeChecker report format.
 */
abstract class AnalyzerRunner extends BaseObject
{
    protected ?string $workingDirBasePath;
    protected StudentFile $studentFile;

    /**
     * @param $studentFile
     */
    public function __construct(StudentFile $studentFile)
    {
        parent::__construct();
        $this->studentFile = $studentFile;
        $this->workingDirBasePath = null;
    }

    /**
     * A template method that performs the steps of static code analysis.
     * @return array{
     *  tarPath: string|null,
     *  exitCode: int,
     *  stdout: string,
     *  stderr: string
     * }
     * @throws CodeCheckerRunnerException
     */
    public function run(): array
    {
        $this->beforeRun();
        $this->initWorkDir();

        if (!$this->prepareStudentFiles() || !$this->prepareInstructorFiles() || !$this->prepareAnalyzeInstructions()) {
            throw new CodeCheckerRunnerException(
                Yii::t("app", "Failed to prepare work directory"),
                CodeCheckerRunnerException::PREPARE_FAILURE
            );
        }
        $this->prepareSkipFile(); // Skip file is optional

        $container = $this->buildAnalyzerContainer();
        try {
            $container->startContainer();
            $this->copyFiles($container);
            $analyzeResult = $this->execAnalyzeCommand($container);
            $tarPath = $analyzeResult["exitCode"] !== 0
                ? $this->createAndDownloadReportsTar($container)
                : null;
            return [
                'tarPath' => $tarPath,
                'exitCode' => $analyzeResult['exitCode'],
                'stdout' => $analyzeResult['stdout'],
                'stderr' => $analyzeResult['stderr']
            ];
        } finally {
            $container->stopContainer();
        }
    }

    /**
     * Contains logic that runs before code analyzers.
     * @throws CodeCheckerRunnerException Thrown if a precondition fails
     * @return void
     */
    protected function beforeRun()
    {
        $dockerImageManager = Yii::$container->get(
            DockerImageManager::class,
            ['os' => $this->studentFile->task->testOS]
        );
        if (!$dockerImageManager->alreadyBuilt($this->studentFile->task->imageName)) {
            throw new CodeCheckerRunnerException(
                Yii::t("app", "Evaluator Docker image is not available"),
                CodeCheckerRunnerException::BEFORE_RUN_FAILURE
            );
        }
    }

    /**
     * Prepares script file that runs the analyzer instructions.
     * @return bool
     */
    abstract protected function prepareAnalyzeInstructions(): bool;

    /**
     * Creates and downloads a tar file that contains the CodeChecker reports in html and json formats.
     * @param DockerContainer $analyzerContainer The container that contains that runs the analyzer tool
     * @return string|null
     */
    abstract protected function createAndDownloadReportsTar(DockerContainer $analyzerContainer): ?string;

    /**
     * Copies CodeChecker skipfile contents to the container if they are available.
     * Skipfile is a filter listing which files should or should not be analyzed.
     * Syntax: https://codechecker.readthedocs.io/en/latest/analyzer/user_guide/#skip
     * @return void
     */
    private function prepareSkipFile()
    {
        if (!empty($this->studentFile->task->codeCheckerSkipFile)) {
            $this->placeTextFileToWorkdir("skipfile", $this->studentFile->task->codeCheckerSkipFile);
        }
    }

    /**
     * Build container for the current run
     * @return DockerContainer
     * @throws \yii\base\Exception
     */
    protected function buildAnalyzerContainer(): DockerContainer
    {
        return DockerContainerBuilder::forTask($this->studentFile->task)
            ->build("tms_codechecker_{$this->studentFile->id}");
    }

    /**
     *
     * @param DockerContainer $dockerContainer
     * @return array{
     *  exitCode: int,
     *  stdout: string,
     *  stderr: string
     * }
     */
    private function execAnalyzeCommand(DockerContainer $dockerContainer): array
    {
        if ($this->studentFile->task->testOS == 'windows') {
            $ccCommand = ['powershell', 'C:\\test\\analyze.ps1'];
        } else {
            $ccCommand = [
                'timeout',
                Yii::$app->params['evaluator']['staticAnalysisTimeout'],
                '/bin/bash',
                '/test/analyze.sh'
            ];
        }

        return $dockerContainer->executeCommand($ccCommand);
    }

    /**
     * Parses CodeChecker plist reports.
     * @param DockerContainer $dockerContainer
     * @param string $format Possible values: json, html
     * @return void
     * @throws CodeCheckerRunnerException
     */
    protected function runParseCommand(DockerContainer $dockerContainer, string $format)
    {
        if ($this->studentFile->task->testOS === 'linux') {
            $prefix = "/test/submission";
            $plistReportsDir = "/test/reports/plist";
            $outputDir = $format === "json" ? "/test/reports/reports.json" : "/test/reports/html";
            $skipFilePath = "/test/skipfile";
        } else {
            $prefix = "C:\\test\\submission";
            $plistReportsDir = "C:\\test\\reports\\plist";
            $outputDir = $format === "json" ? "C:\\test\\reports\\reports.json" : "C:\\test\\reports\\html";
            $skipFilePath = "C:\\test\\skipfile";
        }
        $command = [
            "CodeChecker", "parse", $plistReportsDir,
            "--export", $format,
            "--output", $outputDir,
            "--trim-path-prefix", $prefix
        ];

        if (!empty($this->studentFile->task->codeCheckerSkipFile)) {
            $command[] = "--ignore";
            $command[] = $skipFilePath;
        }
        $result =  $dockerContainer->executeCommand($command);


        /*
            Known error codes:
                0 - No report
                1 - CodeChecker error
                2 - At least one report emitted by an analyzer
            Other error should be also handled
         */
        if ($result['exitCode'] !== 0 && $result['exitCode'] !== 2) {
            throw new CodeCheckerRunnerException(
                Yii::t("app", "Failed to parse reports and save the result to {format}", ['format' => $format]),
                CodeCheckerRunnerException::PARSE_FAILURE,
                $result
            );
        }
    }

    // TODO this code can be shared with AssignmentTester and SubmissionRunner

    /**
     * Initializes working directory for student and instructor files
     * @return void
     */
    protected function initWorkDir()
    {
        $this->workingDirBasePath =
            Yii::$app->basePath
            . '/'
            . Yii::$app->params['data_dir']
            . '/tmp/codechecker/'
            . Yii::$app->security->generateRandomString(4)
            . '/';

        FileHelper::createDirectory($this->workingDirBasePath, 0755, true);
    }

    /**
     * Extracts the student solution to workdir
     *
     * @return bool The success of extraction.
     */
    protected function prepareStudentFiles(): bool
    {
        $submissionDir = $this->workingDirBasePath . 'submission/';

        if (!file_exists($submissionDir)) {
            FileHelper::createDirectory($submissionDir, 0755, true);
        }

        $zip = new \ZipArchive();
        $res = $zip->open($this->studentFile->path);
        if ($res === true) {
            $zip->extractTo($submissionDir);
            $zip->close();
            return true;
        } else {
            return false;
        }
    }

    /**
     * Copies the instructor defined test files of the task to workdir
     *
     * @return bool The success of the copy operations.
     */
    protected function prepareInstructorFiles(): bool
    {
        $testFileDir = $this->workingDirBasePath . 'test_files/';

        if (!file_exists($testFileDir)) {
            FileHelper::createDirectory($testFileDir, 0755, true);
        }

        $testFiles = InstructorFile::find()
            ->where(['taskID' => $this->studentFile->taskID])
            ->onlyTestFiles()
            ->all();

        $success = true;
        foreach ($testFiles as $testFile) {
            $success = $success && copy($testFile->path, $testFileDir . '/' . $testFile->name);
        }
        return $success;
    }

    /**
     * Places a text file to the working directory
     * @param string $fileName filename with extension
     * @param string $content
     * @return bool
     */
    protected function placeTextFileToWorkdir(string $fileName, string $content): bool
    {
        $path = $this->workingDirBasePath . $fileName;
        return file_put_contents($path, $content) && chmod($path, 0755);
    }

    /**
     * @throws CodeCheckerRunnerException
     */
    protected function copyFiles(DockerContainer $dockerContainer)
    {
        // send student solution to docker container as TAR stream
        try {
            $tarPath = $this->workingDirBasePath . 'test.tar';
            $phar = new \PharData($tarPath);
            $phar->buildFromDirectory($this->workingDirBasePath);
            $dockerContainer->uploadArchive(
                $tarPath,
                $this->studentFile->task->testOS == 'windows' ? 'C:\\test' : '/test'
            );
        } catch (\Throwable $e) {
            throw new CodeCheckerRunnerException(
                Yii::t("app", "Failed to package and upload tar archive to the container: ") . $e->getMessage(),
                CodeCheckerRunnerException::PREPARE_FAILURE
            );
        }
    }

    /**
     * Deletes the student solution and related files from tmp/docker/{workingDirBasePath}
     * @throws \yii\base\ErrorException Thrown if failed to delete the directory
     */
    public function deleteWorkDirectory()
    {
        if (is_dir($this->workingDirBasePath)) {
            FileHelper::removeDirectory($this->workingDirBasePath);
        }
    }
}
