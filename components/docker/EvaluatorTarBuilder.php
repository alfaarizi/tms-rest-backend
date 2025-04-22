<?php

namespace app\components\docker;

use app\exceptions\EvaluatorTarBuilderException;
use app\models\TaskFile;
use PharData;
use Throwable;
use Yii;
use yii\base\BaseObject;
use yii\base\Exception;
use yii\helpers\FileHelper;
use ZipArchive;

/**
 * A fluent builder for creating tar files for evaluator Docker containers
 */
class EvaluatorTarBuilder extends BaseObject
{
    public const DEFAULT_SUBMISSION_DIR = "submission";
    public const DEFAULT_TEST_FILES_DIR = "test_files";

    private string $workDirPath;
    private string $tarPath;

    /**
     * @param string $basePath Base directory where workspace and tar file will be placed
     * @param string $name The name of the work directory and tar file that will be placed to the provided basePath
     */
    public function __construct(string $basePath, string $name)
    {
        parent::__construct();
        $this->workDirPath = "$basePath/$name/";
        $this->tarPath = "$basePath/$name.tar";
    }

    /**
     * Extracts the student solution to the test directory
     * @param string $sourceZipPath Zip file containing the students files
     * @param string $destPath The path where the student files will be placed in the tar file
     * @return $this
     * @throws EvaluatorTarBuilderException
     */
    public function withSubmission(
        string $sourceZipPath,
        string $destPath = self::DEFAULT_SUBMISSION_DIR
    ): EvaluatorTarBuilder {
        $this->ensureCreated();
        $submissionDir = $this->workDirPath . $destPath . '/';

        if (!file_exists($submissionDir)) {
            try {
                FileHelper::createDirectory($submissionDir, 0755, true);
            } catch (Exception $e) {
                throw new EvaluatorTarBuilderException(
                    Yii::t('app', 'Failed to init directory for submission')
                );
            }
        }

        $zip = new ZipArchive();
        $res = $zip->open($sourceZipPath);
        if ($res === true) {
            $zip->extractTo($submissionDir);
            $zip->close();
            return $this;
        } else {
            throw new EvaluatorTarBuilderException(
                'Failed to add submission',
                EvaluatorTarBuilderException::ADD
            );
        }
    }

    /**
     * Copies the instructor defined test files of the task to the test directory
     *
     * @throws EvaluatorTarBuilderException
     */
    public function withInstructorTestFiles(
        int $taskId,
        string $dirName = self::DEFAULT_TEST_FILES_DIR
    ): EvaluatorTarBuilder {
        $this->ensureCreated();
        $testFileDir = $this->workDirPath . $dirName . '/';

        if (!file_exists($testFileDir)) {
            try {
                FileHelper::createDirectory($testFileDir, 0755, true);
            } catch (Exception $e) {
                throw new EvaluatorTarBuilderException(
                    Yii::t('app', 'Failed to init directory for instructor test files'),
                    EvaluatorTarBuilderException::ADD
                );
            }
        }

        $testFiles = TaskFile::find()
            ->where(['taskID' => $taskId])
            ->onlyTestFiles()
            ->all();

        foreach ($testFiles as $testFile) {
            if (!copy($testFile->path, $testFileDir . '/' . $testFile->name)) {
                throw new EvaluatorTarBuilderException(
                    Yii::t('app', 'Failed to add instructor test file (#{id})', ['id' => $testFile->id]),
                    EvaluatorTarBuilderException::ADD
                );
            }
        }
        return $this;
    }

    /**
     * Places a text file to the test directory
     * @param string $relPath path to the file in the tar file
     * @param string|null $content content
     * @param bool $skipEmptyContent
     * @return EvaluatorTarBuilder
     * @throws EvaluatorTarBuilderException
     */
    public function withTextFile(
        string $relPath,
        ?string $content,
        bool $skipEmptyContent = false
    ): EvaluatorTarBuilder {
        if (empty($content)) {
            if ($skipEmptyContent) {
                return $this;
            }
            throw new EvaluatorTarBuilderException(
                Yii::t('app', 'File content is empty ({name})', ['name' => $relPath]),
                EvaluatorTarBuilderException::ADD
            );
        }

        $this->ensureCreated();
        $path = $this->workDirPath . $relPath;
        if (!file_put_contents($path, $content) && !chmod($path, 0755)) {
            throw new EvaluatorTarBuilderException(
                Yii::t('app', 'Failed to add file ({name})', ['name' => $relPath]),
                EvaluatorTarBuilderException::ADD
            );
        }
        return $this;
    }

    /**
     * Build a tar file from the test directory
     * @throws EvaluatorTarBuilderException
     */
    public function buildTar(): string
    {
        $this->ensureCreated();
        try {
            if (is_file($this->tarPath)) {
                FileHelper::unlink($this->tarPath);
            }
            $phar = new PharData($this->tarPath);
            $phar->buildFromDirectory($this->workDirPath);
            return $this->tarPath;
        } catch (\Exception $e) {
            throw new EvaluatorTarBuilderException($e->getMessage(), EvaluatorTarBuilderException::BUILD, $e);
        }
    }

    /**
     * Deletes the working directory and the tar file. Resets the builder.
     * @return EvaluatorTarBuilder
     * @throws EvaluatorTarBuilderException
     */
    public function cleanup(): EvaluatorTarBuilder
    {
        try {
            if (is_file($this->tarPath)) {
                FileHelper::unlink($this->tarPath);
            }
            if (is_dir($this->workDirPath)) {
                FileHelper::removeDirectory($this->workDirPath);
            }
            return $this;
        } catch (\Exception $e) {
            throw new EvaluatorTarBuilderException($e->getMessage(), EvaluatorTarBuilderException::CLEANUP, $e);
        }
    }

    /**
     * @throws EvaluatorTarBuilderException
     */
    private function ensureCreated()
    {
        if (is_dir($this->workDirPath)) {
            return;
        }
        try {
            FileHelper::createDirectory($this->workDirPath, 0755, true);
        } catch (Exception $e) {
            throw new EvaluatorTarBuilderException(
                Yii::t('app', 'Failed to init builder working directory'),
                EvaluatorTarBuilderException::ADD
            );
        }
    }
}
