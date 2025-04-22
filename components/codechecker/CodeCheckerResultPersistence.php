<?php

namespace app\components\codechecker;

use app\exceptions\CodeCheckerPersistenceException;
use app\exceptions\CodeCheckerResultNotifierException;
use app\models\CodeCheckerReport;
use app\models\CodeCheckerResult;
use app\models\Submission;
use PharData;
use Throwable;
use Yii;
use yii\base\BaseObject;
use yii\base\ErrorException;
use yii\base\InvalidArgumentException;
use yii\base\InvalidConfigException;
use yii\db\Exception;
use yii\di\NotInstantiableException;
use yii\helpers\FileHelper;

class CodeCheckerResultPersistence extends BaseObject
{
    private Submission $submission;
    private CodeCheckerResultNotifier $notifier;

    /**
     * @param Submission $submission
     * @throws InvalidConfigException Thrown if it failed to get dependencies from the DI container
     *  because of configuration errors
     * @throws NotInstantiableException Thrown if it failed to get dependencies from the DI container
     */
    public function __construct(Submission $submission)
    {
        parent::__construct();
        $this->submission = $submission;
        $this->notifier = Yii::$container->get(CodeCheckerResultNotifier::class);
    }

    /**
     * If the codeCheckerResult field of the current student file is null,
     * then create a new result with 'In Progress' status.
     * @return void
     * @throws CodeCheckerPersistenceException
     */
    public function createNewResult()
    {
        if (!is_null($this->submission->codeCheckerResultID)) {
            throw new CodeCheckerPersistenceException("CodeChecker result is already set for this student file");
        }

        $transaction = Yii::$app->getDb()->beginTransaction();
        try {
            $result = new CodeCheckerResult();
            $result->token = Yii::$app->security->generateRandomString(32);
            $result->submissionID = $this->submission->id;
            $result->status = CodeCheckerResult::STATUS_IN_PROGRESS;
            $result->createdAt = date('Y-m-d H:i:s');
            if (!$result->save()) {
                throw new CodeCheckerPersistenceException("Failed to save CodeChecker result to the database");
            }
            $this->submission->codeCheckerResultID = $result->id;
            if (!$this->submission->save()) {
                throw new CodeCheckerPersistenceException("Failed to modify student file");
            }
            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollback();
            throw new CodeCheckerPersistenceException(
                "Unable to create new result for student file {$e->getMessage()}" . PHP_EOL
            );
        }
    }

    /**
     * Saves the result of a run into the codeCheckerResult record of the current student file.
     * The codeCheckerResult field must be set, and the status must be 'In Progress'.
     * @param ?string $tarPath Path of the tar file that contain analyzer results
     * @param int $exitCode Analyzer exit code
     * @param string $stdout Standard output of the run
     * @param string $stderr Standard error of the run
     * @return void
     * @throws CodeCheckerPersistenceException
     * @throws CodeCheckerResultNotifierException
     */
    public function saveResult(?string $tarPath, int $exitCode, string $stdout, string $stderr): void
    {
        if ($exitCode === 0 && !is_null($tarPath)) {
            throw new \InvalidArgumentException("Exit code is 0, but a tar file is attached");
        }

        if (is_null($this->submission->codeCheckerResultID)) {
            throw new CodeCheckerPersistenceException("CodeChecker result not found");
        }

        $result = $this->submission->codeCheckerResult;
        if ($result->status !== CodeCheckerResult::STATUS_IN_PROGRESS) {
            throw new CodeCheckerPersistenceException("CodeChecker result is already saved");
        }

        $result->stdout = substr($stdout, 0, 65000);
        $result->stderr = substr($stderr, 0, 65000);

        if (is_null($tarPath)) {
            $result->status = $exitCode === 0
                ? CodeCheckerResult::STATUS_NO_ISSUES
                : CodeCheckerResult::STATUS_ANALYSIS_FAILED;
            if (!$result->save()) {
                throw new CodeCheckerPersistenceException("Failed to save CodeChecker result to the database");
            }
            $this->notifier->sendNotifications($this->submission);
        } else {
            $workDir = $this->createWorkDir();
            $transaction = Yii::$app->getDb()->beginTransaction();
            try {
                $this->extractTar($tarPath, $workDir);
                $reportsJson = $this->getReportsArray($workDir);
                if (count($reportsJson) > 0) {
                    $result->status = CodeCheckerResult::STATUS_ISSUES_FOUND;
                    $this->saveReportsToDb($result->id, $reportsJson);
                    $this->copyHtmlReports($result->id, $workDir);
                } else {
                    $result->status = CodeCheckerResult::STATUS_ANALYSIS_FAILED;
                }

                if (!$result->save()) {
                    throw new CodeCheckerPersistenceException("Failed to save CodeChecker result to the database");
                }
                $transaction->commit();

                // Notification should be sent after the changes are commit,
                // so the results are persisted even if notifications are failed
                $this->notifier->sendNotifications($this->submission);
            } catch (CodeCheckerPersistenceException $e) {
                $transaction->rollBack();
                throw $e;
            } catch (\Exception $e) {
                throw new CodeCheckerPersistenceException("Failed to commit transaction");
            } finally {
                $this->deleteTemporaryFiles($workDir);
            }
        }
    }

    /**
     * Saves the result with the provided error message and 'Runner Error' status
     * @param string $errorMessage
     * @return void
     * @throws CodeCheckerPersistenceException
     * @throws CodeCheckerResultNotifierException
     */
    public function saveRunnerError(string $errorMessage)
    {
        if (is_null($this->submission->codeCheckerResultID)) {
            throw new CodeCheckerPersistenceException("CodeChecker result not found");
        }

        $result = $this->submission->codeCheckerResult;
        if ($result->status !== CodeCheckerResult::STATUS_IN_PROGRESS) {
            throw new CodeCheckerPersistenceException("CodeChecker result is already saved");
        }

        $result->status = CodeCheckerResult::STATUS_RUNNER_ERROR;
        $result->runnerErrorMessage = substr($errorMessage, 0, 65000);
        if (!$result->save()) {
            throw new CodeCheckerPersistenceException("Failed to modify result");
        }
        $this->notifier->sendNotifications($this->submission);
    }

    /**
     * Creates a temporary folder with a random folder name
     * @return string
     * @throws CodeCheckerPersistenceException Thrown when temporary directory cannot be created
     */
    private function createWorkDir(): string
    {
        try {
            $workDir = Yii::getAlias("@tmp/codechecker/")
                . Yii::$app->security->generateRandomString(4)
                . '/';
            FileHelper::createDirectory($workDir, 0755, true);
            return $workDir;
        } catch (\yii\base\Exception $e) {
            throw new CodeCheckerPersistenceException(Yii::t('app', 'Failed to prepare work directory'));
        }
    }

    /**
     * Extracts the result tar file to the given directory
     * @param string $tarPath
     * @param string $destPath
     * @return void
     * @throws CodeCheckerPersistenceException Thrown when tar file cannot be extracted
     */
    private function extractTar(string $tarPath, string $destPath): void
    {
        if (!is_file($tarPath)) {
            throw new CodeCheckerPersistenceException("Tar file does not exists");
        }

        try {
            $phar = new PharData($tarPath);
            $phar->extractTo($destPath);
        } catch (\Exception $e) {
            throw new CodeCheckerPersistenceException("Failed to extract tar file: " . $tarPath);
        }
    }

    /**
     * Reads and parses the JSON output file of the 'CodeChecker parse' command
     * @param string $workDir
     * @return array
     * @throws CodeCheckerPersistenceException Thrown if the json file is not found ot it has an unsupported version
     */
    private function getReportsArray(string $workDir): array
    {
        $path = $workDir . "/reports/reports.json";
        if (!is_file($path)) {
            throw new CodeCheckerPersistenceException("reports.json file not found");
        }
        $contents = json_decode(file_get_contents($path), true);
        if ($contents["version"] != 1) {
            throw new CodeCheckerPersistenceException("reports.json version is not supported");
        }
        return $contents["reports"];
    }

    /**
     * Maps a report from the CodeChecker JSON output then saves it to the database
     * @param int $resultID The id of the current CodeCheckerResult record
     * @param array $resultJson The parsed contents of the JSON file
     * @return void
     * @throws CodeCheckerPersistenceException
     */
    private function saveReportsToDb(int $resultID, array $resultJson): void
    {
        foreach ($resultJson as $report) {
            $model = new CodeCheckerReport();
            $model->resultID = $resultID;
            $model->category = $report['category'];
            $model->filePath = $report['file']['path'];
            $model->reportHash = $report['report_hash'];
            $model->line = $report['line'];
            $model->column = $report['column'];
            $model->checkerName = $report['checker_name'];
            $model->analyzerName = $report['analyzer_name'];
            $model->severity = ucfirst(strtolower($report['severity']));
            $model->message = $report['message'];
            $model->plistFileName = basename(str_replace('\\', '/', $report['analyzer_result_file_path']));
            if (!$model->save()) {
                throw new CodeCheckerPersistenceException("Failed to save CodeChecker report.");
            }
        }
    }

    /**
     * Copies the HTML output files of the 'CodeChecker parse' command to the folder of the student file
     * @param string $runId
     * @param string $workDir
     * @return void
     * @throws CodeCheckerPersistenceException
     */
    private function copyHtmlReports(string $runId, string $workDir)
    {
        $source = $workDir . "/reports/html";
        if (!is_dir($source)) {
            throw new CodeCheckerPersistenceException("HTML reports directory not found: " . $source);
        }
        $dest = Yii::getAlias("@appdata/codechecker_html_reports/$runId");
        try {
            FileHelper::createDirectory($dest, 0755, true);
            FileHelper::copyDirectory($source, $dest, ['fileMode' => 0775]);
            $this->restoreNonAsciiFileNames($dest);
        } catch (InvalidArgumentException | \yii\base\Exception $e) {
            throw new CodeCheckerPersistenceException("Unable to copy HTML reports: " . $e->getMessage());
        }
    }

    /**
     * Workaround for https://gitlab.com/tms-elte/backend-core/-/issues/122
     * PharData removes non-ascii characters from file names, this can break HTML reports
     * @param string $dir Path of the directory that contains the HTML report files
     * @return void
     */
    private function restoreNonAsciiFileNames(string $dir)
    {
        $renamed = [];
        foreach ($this->submission->codeCheckerResult->codeCheckerReports as $report) {
            $correctName = "$report->plistFileName.html";
            $incorrectName = preg_replace('/[^[:print:]]/', '', $correctName);
            if (
                $correctName !== $incorrectName
                && !array_key_exists($incorrectName, $renamed)
                && is_file("$dir/$incorrectName")
            ) {
                rename("$dir/$incorrectName", "$dir/$correctName");
                $renamed[$incorrectName] = null;
            }
        }
    }

    /**
     * @return void
     * @throws CodeCheckerPersistenceException
     */
    private function deleteTemporaryFiles(string $workDir)
    {
        try {
            if (is_dir($workDir)) {
                FileHelper::removeDirectory($workDir);
            }
        } catch (ErrorException $e) {
            throw new CodeCheckerPersistenceException("Failed to delete temporary files: " . $e->getMessage());
        }
    }
}
