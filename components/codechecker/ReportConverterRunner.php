<?php

namespace app\components\codechecker;

use app\components\docker\DockerContainer;
use app\components\docker\DockerContainerBuilder;
use app\components\docker\EvaluatorTarBuilder;
use app\exceptions\CodeCheckerRunnerException;
use app\models\StudentFile;
use Yii;

/**
 * Runs a static analyzer tool in the configured environment then creates CodeChecker reports from it.
 */
class ReportConverterRunner extends AnalyzerRunner
{
    public function __construct(StudentFile $studentFile)
    {
        parent::__construct($studentFile);
    }

    protected function beforeRun(): void
    {
        parent::beforeRun();
        $os = $this->studentFile->task->testOS;
        $imageName = Yii::$app->params["evaluator"]["reportConverterImage"][$os];

        if (empty($imageName)) {
            throw new CodeCheckerRunnerException(
                Yii::t("app", "CodeChecker Report Converter Docker image is not set in params.php"),
                CodeCheckerRunnerException::BEFORE_RUN_FAILURE
            );
        }

        if (!$this->dockerImageManager->alreadyBuilt($imageName)) {
            throw new CodeCheckerRunnerException(
                Yii::t("app", "CodeChecker Report Converter Docker image is not available"),
                CodeCheckerRunnerException::BEFORE_RUN_FAILURE
            );
        }
    }

    protected function addAnalyzeInstructionsToTar(EvaluatorTarBuilder $tarBuilder): void
    {
        $tarBuilder->withTextFile(
            "analyze" . ($this->studentFile->task->testOS == 'windows' ? '.ps1' : '.sh'),
            $this->studentFile->task->staticCodeAnalyzerInstructions
        );
    }

    /**
     * @param DockerContainer $analyzerContainer
     * @return string|null
     * @throws CodeCheckerRunnerException
     */
    protected function createAndDownloadReportsTar(DockerContainer $analyzerContainer): ?string
    {
        if (!$this->checkIfReportsArePresent($analyzerContainer)) {
            return null;
        }

        $reportConverterContainer = $this->buildReportConverterContainer();
        try {
            $reportConverterContainer->startContainer();
            $this->copyTestDirectoryToConverterContainer($analyzerContainer, $reportConverterContainer);

            $this->runReportConverter($reportConverterContainer);

            $this->runParseCommand($reportConverterContainer);
            $tarPath = $this->workingDirBasePath . "/reports.tar";
            $reportConverterContainer->downloadArchive(
                $this->studentFile->task->testOS  === "windows"
                    ? "C:\\test\\reports" : "/test/reports",
                $tarPath
            );
            return $tarPath;
        } finally {
            $reportConverterContainer->stopContainer();
        }
    }

    /**
     * Checks if the output of the current tool exists in the analyzer container
     * @param DockerContainer $analyzerContainer
     * @return bool
     * @throws CodeCheckerRunnerException
     */
    private function checkIfReportsArePresent(DockerContainer $analyzerContainer): bool
    {
        $supportedTools = Yii::$app->params["evaluator"]["supportedStaticAnalyzerTools"];
        $toolName = $this->studentFile->task->staticCodeAnalyzerTool;
        $outputPath = $supportedTools[$toolName]["outputPath"];
        if (empty($outputPath)) {
            throw new CodeCheckerRunnerException(
                Yii::t("app", "'outputPath' is not provided in params.php for the {toolName} tool", ["toolName" => $toolName])
            );
        }

        if ($this->studentFile->task->testOS == 'windows') {
            $outputPath = str_replace('/', '\\', $outputPath);
            $result = $analyzerContainer->executeCommand(
                ["powershell", "-Command", "Test-Path -Path C:\\test\\$outputPath"]
            );
            return rtrim($result["stdout"]) === "True";
        } else {
            $result = $analyzerContainer->executeCommand(["test", "-e", "/test/$outputPath"]);
            return $result["exitCode"] === 0;
        }
    }

    /**
     * Builds the Docker container that contains the report-converter tool.
     * If the needed image is not present then tries to pull it.
     * @return DockerContainer
     * @throws CodeCheckerRunnerException
     * @throws \yii\base\Exception
     */
    protected function buildReportConverterContainer(): DockerContainer
    {
        $os = $this->studentFile->task->testOS;
        $imageName = Yii::$app->params["evaluator"]["reportConverterImage"][$os];

        $builder = new DockerContainerBuilder($os, $imageName);
        return $builder->build("tms_report_converter_" . $this->studentFile->id);
    }

    /**
     * Copies the contents of the test directory to the container that contains report-converter.
     * @param DockerContainer $analyzerContainer
     * @param DockerContainer $reportConverterContainer
     * @return void
     */
    private function copyTestDirectoryToConverterContainer(
        DockerContainer $analyzerContainer,
        DockerContainer $reportConverterContainer
    ) {
        $tarPath = $this->workingDirBasePath . "/analyzed_test.tar";
        $analyzerContainer->downloadArchive(
            $this->studentFile->task->testOS === "linux" ? "/test" : "C:\\test",
            $tarPath
        );
        $reportConverterContainer->uploadArchive($tarPath, '/');
    }

    /**
     * Runs report converter on the report-converter container
     * @param DockerContainer $container
     * @return void
     */
    private function runReportConverter(DockerContainer $container)
    {
        $toolName = $this->studentFile->task->staticCodeAnalyzerTool;
        $resultFilePath = ($this->studentFile->task->testOS === "windows" ? "C:\\test\\" : "/test/")
            . Yii::$app->params["evaluator"]["supportedStaticAnalyzerTools"][$toolName]["outputPath"];
        $plistPath = $this->studentFile->task->testOS === "windows" ? "C:\\test\\reports\\plist" : "/test/reports/plist";

        $container->executeCommand([
            "report-converter",
            "-t", $this->studentFile->task->staticCodeAnalyzerTool,
            "-o", $plistPath,
            $resultFilePath
        ]);
    }
}
