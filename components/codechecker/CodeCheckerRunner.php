<?php

namespace app\components\codechecker;

use app\components\docker\DockerContainer;
use app\components\docker\EvaluatorTarBuilder;
use app\exceptions\CodeCheckerRunnerException;
use app\models\Submission;

/**
 * Runs CodeChecker on C/C++ solutions
 */
class CodeCheckerRunner extends AnalyzerRunner
{
    public function __construct(Submission $submission)
    {
        parent::__construct($submission);
    }

    protected function addAnalyzeInstructionsToTar(EvaluatorTarBuilder $tarBuilder): void
    {
        $testOS = $this->submission->task->testOS;
        $ext = $testOS == 'windows' ? '.ps1' : '.sh';

        $buildCommand = $testOS === "windows"
            ? "powershell C:\\test\\build.ps1"
            : "bash /test/build.sh";
        $ccCommand = 'CodeChecker check --build "' . $buildCommand . '" --output '
            . ($testOS === 'windows' ? 'C:\\test\\reports\\plist' : '/test/reports/plist');
        if (!empty($this->submission->task->codeCheckerSkipFile)) {
            $ccCommand .= ' --ignore ' . ($testOS == 'windows' ? 'C:\\test\\skipfile' : '/test/skipfile');
        }
        if (!empty($this->submission->task->codeCheckerToggles)) {
            $ccCommand .= ' ' . $this->submission->task->codeCheckerToggles;
        }

        $tarBuilder
            ->withTextFile("build" . $ext, $this->submission->task->codeCheckerCompileInstructions)
            ->withTextFile('analyze' . $ext, $ccCommand);
    }

    /**
     * @param DockerContainer $analyzerContainer
     * @return string|null
     * @throws CodeCheckerRunnerException
     */
    protected function createAndDownloadReportsTar(DockerContainer $analyzerContainer): ?string
    {
        $testOS = $this->submission->task->testOS;
        $this->runParseCommand($analyzerContainer);

        $tarPath = $this->workingDirBasePath . "/reports.tar";
        $analyzerContainer->downloadArchive(
            $testOS === "windows" ? "C:\\test\\reports" : "/test/reports",
            $tarPath
        );
        return $tarPath;
    }
}
