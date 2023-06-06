<?php

namespace app\components\codechecker;

use app\components\docker\DockerContainer;
use app\components\docker\DockerContainerBuilder;
use app\components\docker\DockerImageManager;
use app\components\docker\EvaluatorTarBuilder;
use app\exceptions\CodeCheckerRunnerException;
use app\exceptions\EvaluatorTarBuilderException;
use app\models\StudentFile;
use Throwable;
use Yii;
use yii\base\BaseObject;
use yii\base\ErrorException;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\di\NotInstantiableException;
use yii\helpers\FileHelper;

/**
 * An abstract class that contains the steps to run static code analysis on a student file
 * and retrieves results in the CodeChecker report format.
 */
abstract class AnalyzerRunner extends BaseObject
{
    protected DockerImageManager $dockerImageManager;
    protected ?string $workingDirBasePath;
    protected StudentFile $studentFile;

    /**
     * @param StudentFile $studentFile
     * @throws InvalidConfigException|NotInstantiableException Failed to get dependency from the DI container
     */
    public function __construct(StudentFile $studentFile)
    {
        parent::__construct();
        $this->studentFile = $studentFile;
        $this->workingDirBasePath = null;

        $this->dockerImageManager = Yii::$container->get(
            DockerImageManager::class,
            ['os' => $studentFile->task->testOS]
        );
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
        $testTarPath = $this->createTar();

        $container = $this->buildAndStartAnalyzerContainer();
        try {
            $this->copyFiles($container, $testTarPath);
            $analyzeResult = $this->execAnalyzeCommand($container);
            $reportsTarPath = $analyzeResult["exitCode"] !== 0
                ? $this->createAndDownloadReportsTar($container)
                : null;
            return [
                'tarPath' => $reportsTarPath,
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
     * @return void
     * @throws CodeCheckerRunnerException Thrown if a precondition fails
     */
    protected function beforeRun()
    {
        if (!$this->dockerImageManager->alreadyBuilt($this->studentFile->task->imageName)) {
            throw new CodeCheckerRunnerException(
                Yii::t("app", "Evaluator Docker image is not available"),
                CodeCheckerRunnerException::BEFORE_RUN_FAILURE
            );
        }
    }

    /**
     * Initializes working directory for student and instructor files
     * @return void
     * @throws CodeCheckerRunnerException
     */
    private function initWorkDir()
    {
        try {
            $this->workingDirBasePath =
                Yii::$app->basePath
                . '/'
                . Yii::$app->params['data_dir']
                . '/tmp/codechecker/'
                . Yii::$app->security->generateRandomString(4)
                . '/';

            FileHelper::createDirectory($this->workingDirBasePath, 0755, true);
        } catch (Exception $e) {
            throw new CodeCheckerRunnerException(Yii::t('app', 'Failed to prepare work directory'));
        }
    }

    /**
     * @return string
     * @throws CodeCheckerRunnerException
     */
    private function createTar(): string
    {
        try {
            $tarBuilder = (new EvaluatorTarBuilder($this->workingDirBasePath, 'test'))
                ->withSubmission($this->studentFile->path)
                ->withInstructorTestFiles($this->studentFile->taskID)
                // CodeChecker skipfile: https://codechecker.readthedocs.io/en/latest/analyzer/user_guide/#skip
                ->withTextFile('skipfile', $this->studentFile->task->codeCheckerSkipFile, true);

            $this->addAnalyzeInstructionsToTar($tarBuilder);
            return $tarBuilder->buildTar();
        } catch (EvaluatorTarBuilderException $e) {
            throw new CodeCheckerRunnerException(
                $e->getMessage(),
                CodeCheckerRunnerException::PREPARE_FAILURE,
                null,
                $e
            );
        }
    }

    /**
     * Copy test tar to the container
     * @throws CodeCheckerRunnerException
     */
    private function copyFiles(DockerContainer $dockerContainer, string $tarPath)
    {
        // send student solution to docker container as TAR stream
        try {
            $dockerContainer->uploadArchive(
                $tarPath,
                $this->studentFile->task->testOS == 'windows' ? 'C:\\test' : '/test'
            );
        } catch (Throwable $e) {
            throw new CodeCheckerRunnerException(
                Yii::t("app", "Failed to package and upload tar archive to the container: ")
                . $e->getMessage(),
                CodeCheckerRunnerException::PREPARE_FAILURE
            );
        }
    }

    /**
     * Prepares script file that runs the analyzer instructions.
     * @param EvaluatorTarBuilder $tarBuilder
     * @return void
     * @throws EvaluatorTarBuilderException
     */
    abstract protected function addAnalyzeInstructionsToTar(EvaluatorTarBuilder $tarBuilder): void;

    /**
     * Creates and downloads a tar file that contains the CodeChecker reports in html and json formats.
     * @param DockerContainer $analyzerContainer The container that contains that runs the analyzer tool
     * @return string|null
     */
    abstract protected function createAndDownloadReportsTar(DockerContainer $analyzerContainer): ?string;

    /**
     * Build and start container for the current run
     * @return DockerContainer
     * @throws CodeCheckerRunnerException
     */
    protected function buildAndStartAnalyzerContainer(): DockerContainer
    {
        try {
            $container = DockerContainerBuilder::forTask($this->studentFile->task)
                ->build("tms_codechecker_{$this->studentFile->id}");
            $container->startContainer();
            return $container;
        } catch (\Throwable $e) {
            throw new CodeCheckerRunnerException(
                Yii::t('app', 'Failed to create or start Docker container'),
                CodeCheckerRunnerException::PREPARE_FAILURE,
                null,
                $e
            );
        }
    }

    /**
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
     * @return void
     * @throws CodeCheckerRunnerException
     */
    protected function runParseCommand(DockerContainer $dockerContainer)
    {
        $formats = [
            'json' => 'reports.json',
            'html' => 'html'
        ];

        foreach (array_keys($formats) as $format) {
            if ($this->studentFile->task->testOS === 'linux') {
                $prefix = "/test/submission";
                $plistReportsDir = "/test/reports/plist";
                $outputDir = "/test/reports/$formats[$format]";
                $skipFilePath = "/test/skipfile";
            } else {
                $prefix = "C:\\test\\submission";
                $plistReportsDir = "C:\\test\\reports\\plist";
                $outputDir = "C:\\test\\reports\\$formats[$format]";
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
            $result = $dockerContainer->executeCommand($command);

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
    }

    /**
     * Deletes the student solution and related files from tmp/docker/{workingDirBasePath}
     * @throws ErrorException Thrown if failed to delete the directory
     */
    public function deleteWorkDirectory()
    {
        if (is_dir($this->workingDirBasePath)) {
            FileHelper::removeDirectory($this->workingDirBasePath);
        }
    }
}
