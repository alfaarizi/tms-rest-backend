<?php

namespace app\components\plagiarism;

use app\models\Plagiarism;
use app\models\Submission;
use Yii;
use yii\helpers\FileHelper;
use yii\web\BadRequestHttpException;

abstract class AbstractPlagiarismFinder
{
    /**
     * The Plagiarism object describing the service-independent
     *  properties of the plagiarism check
     */
    protected Plagiarism $plagiarism;
    /** Temporary directory to store files being submitted in */
    protected string $plagiarismPath;
    /** Detected language of the files */
    protected string $lang;

    public static function getResultDirectory(int $plagiarismId): string
    {
        $basePath = Yii::getAlias("@appdata/plagiarism/plagiarism-result");
        if (!is_dir($basePath)) {
            FileHelper::createDirectory($basePath, 0755, false);
        }
        return "$basePath/$plagiarismId";
    }

    /** Whether this plagiarism finder is enabled at the moment. */
    abstract public static function isEnabled(): bool;

    /**
     * Constructor.
     * @param Plagiarism $plagiarism The plagiarism check to execute
     */
    public function __construct(Plagiarism $plagiarism)
    {
        $this->plagiarism = $plagiarism;
        $tempDir = Yii::getAlias("@tmp");
        $this->plagiarismPath = "$tempDir/plagiarism/{$plagiarism->id}";
    }

    /**
     * Get languages for the given file extension.
     * @return string[]|null Array of languages, or `null` if the extension is not supported at all.
     */
    abstract protected function getExtensionLanguages(string $ext): ?array;

    /**
     * Get a unique file name where a file will be unzipped. By default, it’s just the file name,
     * but it can be overridden (e.g. Moss doesn’t support directory structures).
     * @param string $rootPath The path of the directory for the student file being processed.
     * @param string $filename The path of the current file within the student file ZIP.
     * @return string The path the file will be unzipped to, relative to $rootPath.
     */
    protected function getStoredFileName(string $rootPath, string $filename): string
    {
        return $filename;
    }

    protected function setupTemporaryFiles(): void
    {
        // Delete possible leftover extraction from a previous, aborted attempt.
        if (file_exists($this->plagiarismPath)) {
            $this->deleteTemporaryFiles();
        }

        // Create a folder to the plagiarism validation.
        FileHelper::createDirectory($this->plagiarismPath, 0755, true);

        $zip = new \ZipArchive();
        $languageCounter = [];

        // Iterate through on the tasks.
        foreach ($this->plagiarism->submissions as $submission) {
            // Skip submissions without valid upload
            if (in_array($submission->status, [Submission::STATUS_NO_SUBMISSION, Submission::STATUS_CORRUPTED])) {
                continue;
            }

            // Get the uploaded zip for version controlled and non version controlled tasks as well
            $zipfile = $submission->path;

            // Open the zip for reading.
            $res = $zip->open($zipfile);
            if ($res === true) {
                $path = $this->plagiarismPath . '/' . $submission->taskID . '/' . strtolower($submission->uploader->userCode);
                if (!file_exists($path)) {
                    FileHelper::createDirectory($path, 0755, true);
                }

                // Check all the files within the zip.
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $filename = $zip->getNameIndex($i);
                    $ext = pathinfo($filename)['extension'] ?? null;
                    if ($ext === null) {
                        continue;
                    }

                    $ext = strtolower($ext);
                    // If the extension is invalid then skip the file.
                    if (
                        $ext === 'jpg' || $ext === 'pdf' || $ext === 'odt' ||
                        $ext === 'png' || $ext === 'docx' || $ext === 'exe' || $ext === 'pdb'
                    ) {
                        continue;
                    }

                    // If the extension is supported:
                    $langs = $this->getExtensionLanguages($ext);
                    if ($langs !== null) {
                        // Increment the appropriate counters.
                        foreach ($langs as $lang) {
                            if (!isset($languageCounter[$lang])) {
                                $languageCounter[$lang] = 0;
                            }
                            ++$languageCounter[$lang];
                        }

                        // Copy the file into the plagiarism validation folder.
                        $filepath = $path . '/' . $this->getStoredFileName($path, $filename);
                        is_dir(dirname($filepath)) or FileHelper::createDirectory(dirname($filepath), 0755, true);
                        copy("zip://$zipfile#$filename", $filepath);
                    }
                }
                // Close the zip.
                $zip->close();
            } else {
                Yii::warning("Failed to open '$zipfile': $res", __CLASS__);
            }
        }

        // Set the validation language to the one which was present most often.
        if (empty($languageCounter)) {
            throw new BadRequestHttpException(Yii::t('app', 'Submissions contain no supported file formats.'));
        }
        arsort($languageCounter);
        reset($languageCounter);
        $this->lang = key($languageCounter);
    }

    protected function preProcess(): void
    {
        $this->setupTemporaryFiles();
    }

    abstract protected function findPlagiarisms(): void;

    protected function postProcess(): void
    {
        $this->deleteTemporaryFiles();
    }

    protected function deleteTemporaryFiles(): void
    {
        FileHelper::removeDirectory($this->plagiarismPath);
    }

    final public function start(): void
    {
        $this->preProcess();
        $this->findPlagiarisms();
        $this->postProcess();
    }
}
