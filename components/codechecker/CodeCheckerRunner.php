<?php

namespace app\components\codechecker;

use app\components\docker\DockerContainer;
use app\models\StudentFile;

/**
 * Runs CodeChecker on C/C++ solutions
 */
class CodeCheckerRunner extends AnalyzerRunner
{
    public function __construct(StudentFile $studentFile)
    {
        parent::__construct($studentFile);
    }

    protected function prepareAnalyzeInstructions(): bool
    {
        $testOS = $this->studentFile->task->testOS;
        $result = $this->placeTextFileToWorkdir(
            "build." . ($testOS == 'windows' ? 'ps1' : 'sh'),
            $this->studentFile->task->codeCheckerCompileInstructions
        );
        if ($result) {
            $buildCommand = $testOS === "windows"
                ? "powershell C:\\test\\build.ps1"
                : "bash /test/build.sh";
            $ccCommand = 'CodeChecker check --build "' . $buildCommand . '" --output '
                . ($testOS === 'windows' ? 'C:\\test\\reports\\plist' : '/test/reports/plist');
            if (!empty($this->studentFile->task->codeCheckerSkipFile)) {
                $ccCommand .= ' --ignore ' . ($testOS == 'windows' ? 'C:\\test\\skipfile' : '/test/skipfile');
            }
            if (!empty($this->studentFile->task->codeCheckerToggles)) {
                $ccCommand .= ' ' . $this->studentFile->task->codeCheckerToggles;
            }
            return $this->placeTextFileToWorkdir(
                "analyze." . ($testOS == 'windows' ? 'ps1' : 'sh'),
                $ccCommand
            );
        }
        return false;
    }

    protected function createAndDownloadReportsTar(DockerContainer $analyzerContainer): ?string
    {
        $testOS = $this->studentFile->task->testOS;
        $this->runParseCommand($analyzerContainer, "json");
        $this->runParseCommand($analyzerContainer, "html");

        $tarPath = $this->workingDirBasePath . "/reports.tar";
        $analyzerContainer->downloadArchive(
            $testOS === "windows" ? "C:\\test\\reports" : "/test/reports",
            $tarPath
        );
        return $tarPath;
    }
}
