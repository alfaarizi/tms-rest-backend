<?php

namespace app\commands;

use app\components\codechecker\AnalyzerRunner;
use app\components\codechecker\CodeCheckerResultPersistence;
use app\components\codechecker\SubmissionToAnalyzeFinder;
use app\components\docker\DockerImageManager;
use app\exceptions\CodeCheckerPersistenceException;
use app\exceptions\CodeCheckerResultNotifierException;
use app\exceptions\CodeCheckerRunnerException;
use app\models\Task;
use Yii;
use yii\base\ErrorException;
use yii\base\InvalidConfigException;
use yii\console\ExitCode;
use yii\di\NotInstantiableException;
use yii\helpers\Console;

class CodeCheckerController extends BaseController
{
    /**
     * Runs static analysis on the oldest student file with the least upload count.
     * @param int $count The number of solutions to be checked
     * @return int
     */
    public function actionCheck(int $count = 1): int
    {
        if (!Yii::$app->params['evaluator']['enabled']) {
            $this->stderr('Evaluator is disabled in configuration.' . PHP_EOL, Console::FG_RED);
            return ExitCode::CONFIG;
        }

        try {
            $finder = Yii::$container->get(SubmissionToAnalyzeFinder::class);
        } catch (NotInstantiableException | InvalidConfigException $e) {
            $this->stderr("Unable to get AnalyzerRunnerFactory from the DI container: {$e->getMessage()}" . PHP_EOL);
            return ExitCode::CONFIG;
        }

        for ($i = 0; $i < $count; ++$i) {
            // Find next student file. Exit if there is no file to analyze.
            $submission = $finder->findNext();
            if (!$submission) {
                $this->stdout('No submission found.' . PHP_EOL);
                return ExitCode::OK;
            }

            // Set language to the current user's language
            $originalLanguage = Yii::$app->language;
            Yii::$app->language = $submission->uploader->locale;

            // Select to suitable analyzer runner for the selected tool and persistence for the file
            try {
                $runner = Yii::$container->get(AnalyzerRunner::class, ['submission' => $submission]);
            } catch (NotInstantiableException | InvalidConfigException $e) {
                $this->stderr("Unable to get suitable analyzer runner from the DI container for #{$submission->id}: {$e->getMessage()}" . PHP_EOL);
                return ExitCode::CONFIG;
            }

            try {
                $persistence = Yii::$container->get(CodeCheckerResultPersistence::class, ['submission' => $submission]);
            } catch (NotInstantiableException | InvalidConfigException $e) {
                $this->stderr("Unable to get CodeCheckerResultPersistence from the DI container: {$e->getMessage()}" . PHP_EOL);
                return ExitCode::CONFIG;
            }

            try {
                $this->stdout("Started to analyze student file #{$submission->id}" . PHP_EOL);

                $persistence->createNewResult();
                $result = $runner->run();
                $persistence->saveResult($result['tarPath'], $result['exitCode'], $result['stdout'], $result['stderr']);

                $this->stdout(
                    "Saved results for student file #{$submission->id}."
                    . " Status: {$submission->codeCheckerResult->status}" . PHP_EOL
                );
            } catch (CodeCheckerRunnerException $e) {
                $this->stderr(
                    "Failed to run static code analysis for student file #{$submission->id} : {$e->getMessage()}"
                        . PHP_EOL,
                    Console::FG_RED
                );
                $this->saveExceptionMessage($persistence, $e->getMessage(), $submission->id);
            } catch (CodeCheckerPersistenceException $e) {
                $message = "Failed to save results for #{$submission->id}: {$e->getMessage()}";
                $this->stderr($message . PHP_EOL, Console::FG_RED);
                Yii::error($message, __METHOD__);
            } catch (CodeCheckerResultNotifierException $e) {
                $message = "Failed to save send notifications about the updated CodeCheckerResult for #{$submission->id}: {$e->getMessage()}";
                $this->stderr($message . PHP_EOL, Console::FG_RED);
                Yii::error($message, __METHOD__);
            } finally {
                // Restore original language
                Yii::$app->language = $originalLanguage;
                // Delete temporary files
                try {
                    $runner->deleteWorkDirectory();
                } catch (ErrorException $e) {
                    $this->stderr("Failed to cleanup files after run for #{$submission->id}");
                }
            }
        }
        return ExitCode::OK;
    }

    /**
     * Calls the saveRunnerError method of the persistence and handle errors from that method
     * @param CodeCheckerResultPersistence $persistence
     * @param string $exceptionMessage
     * @param int $submissionID ID of the current student file
     * @return void
     */
    private function saveExceptionMessage(CodeCheckerResultPersistence $persistence, string $exceptionMessage, int $submissionID)
    {
        try {
            $persistence->saveRunnerError($exceptionMessage);
        } catch (CodeCheckerPersistenceException $e) {
            $message = "Failed to save error message to the CodeCheckerResult for #{$submissionID}: {$e->getMessage()}";
            $this->stderr($message . PHP_EOL, Console::FG_RED);
            Yii::error($message, __METHOD__);
        } catch (CodeCheckerResultNotifierException $e) {
            $message = "Failed to save send notifications about the updated unsuccessful CodeChecker run for #{$exceptionMessage}: {$e->getMessage()}";
            $this->stderr($message . PHP_EOL, Console::FG_RED);
            Yii::error($message, __METHOD__);
        }
    }

    /**
     * Pulls the latest version of the CodeChecker report-converter Docker image
     * @param string $os The os of the configured Docker daemon in params.php
     * @return int
     */
    public function actionPullReportConverterImage(string $os): int
    {
        if (!Yii::$app->params['evaluator']['enabled']) {
            $this->stderr('Evaluator is disabled in configuration.' . PHP_EOL, Console::FG_RED);
            return ExitCode::CONFIG;
        }

        $osMap = Task::testOSMap();
        if (!array_key_exists($os, $osMap)) {
            $this->stderr(
                "Docker is not configured for the provided os: $os. Currently supported values: "
                    . join(', ', array_keys($osMap)) . PHP_EOL,
                Console::FG_RED
            );
            return ExitCode::USAGE;
        }
        $imageName = Yii::$app->params['evaluator']['reportConverterImage'][$os];
        if (empty($imageName)) {
            $this->stderr(
                'CodeChecker Report Converter Docker image is not set in configuration' . PHP_EOL,
                Console::FG_RED
            );
            return ExitCode::CONFIG;
        }

        try {
            $this->stdout("Pulling image: $imageName" . PHP_EOL);
            $dockerImageManager = Yii::$container->get(DockerImageManager::class, ['os' => $os]);
            $dockerImageManager->pullImage($imageName);
            $this->stdout("Pulled image: $imageName" . PHP_EOL, Console::FG_GREEN);
        } catch (NotInstantiableException | InvalidConfigException $e) {
            $this->stderr("Unable to get DockerImageManager from the DI container: {$e->getMessage()}" . PHP_EOL);
            return ExitCode::CONFIG;
        } catch (\Exception $e) {
            $this->stderr("Unexpected error, failed to pull docker image: {$e->getMessage()}" . PHP_EOL);
            return  ExitCode::UNSPECIFIED_ERROR;
        }

        return ExitCode::OK;
    }
}
