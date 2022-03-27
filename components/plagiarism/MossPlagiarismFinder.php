<?php

namespace app\components\plagiarism;

use Yii;
use app\models\User;
use app\models\StudentFile;
use app\models\Plagiarism;
use yii\helpers\FileHelper;
use yii\web\BadRequestHttpException;

class MossPlagiarismFinder extends AbstractPlagiarismFinder
{
    /** URL of the plagiarism check result */
    private ?string $mossUrl;
    /**
     * Authorization token to store with the plagiarism and
     * burn in the downloaded files
     */
    private string $token;
    /** Working directory before changing it to upload files */
    private ?string $cwd;
    /** Directory in which uploaded files are stored */
    private string $uploadedFilesPath;
    /** Temporary directory to store files being submitted in */
    private string $plagiarismPath;

    /**
     * The Plagiarism object describing the service-independent
     *  properties of the plagiarism check
     */
    private ?Plagiarism $plagiarism;
    /** The third-party Moss handler */
    private Moss $moss;

    /**
     * Constructor.
     * @param int $plagiarismId Numeric ID of the plagiarism check to execute
     */
    public function __construct(int $plagiarismId)
    {
        parent::__construct($plagiarismId);
        $this->token = Yii::$app->security->generateRandomString();
        $this->moss = $this->getMoss();
        $dataDir = Yii::$app->basePath . '/' . Yii::$app->params['data_dir'];
        $this->uploadedFilesPath = "$dataDir/uploadedfiles";
        $this->plagiarismPath = "$dataDir/plagiarism/$plagiarismId";
    }

    /**
     * Get the third-party Moss handler.
     * Can be overridden in unit tests.
     */
    protected function getMoss(): Moss
    {
        return new Moss(Yii::$app->params['mossId']);
    }

    /**
     * Get the MossDownloader used to download the files.
     * Can be overridden in unit tests.
     */
    protected function getMossDownloader(): MossDownloader
    {
        return new MossDownloader(
            $this->plagiarism->id,
            $this->token,
            $this->mossUrl,
        );
    }

    protected function setupTemporaryFiles()
    {
        // Create a folder to the plagiarism validation.
        if (!file_exists($this->plagiarismPath)) {
            mkdir($this->plagiarismPath, 0755, true);
        }

        $zip = new \ZipArchive();
        $languageCounter = [];
        $supported = $this->moss->getAllowedExtensions();

        // Iterate through on the tasks.
        foreach (explode(',', $this->plagiarism->taskIDs) as $task) {
            $taskPath = $this->uploadedFilesPath . '/' . $task;

            // And through the users.
            foreach (explode(',', $this->plagiarism->userIDs) as $userID) {
                $userNeptun = strtolower(User::findOne($userID)->neptun);
                $studentFile = StudentFile::findOne(['uploaderID' => $userID, 'taskID' => $task]);
                if ($studentFile === null) {
                    continue;
                }
                // Get the uploaded zip for version controlled and non version controlled tasks as well
                $zipfile = $taskPath . '/' . $userNeptun . '/' . $studentFile->name;

                // Open the zip for reading.
                $res = $zip->open($zipfile);
                if ($res === true) {
                    $path = $this->plagiarismPath . '/' . $task . '/' . $userNeptun;
                    if (!file_exists($path)) {
                        mkdir($path, 0755, true);
                    }

                    // Check all the files within the zip.
                    for ($i = 0; $i < $zip->numFiles; $i++) {
                        $filename = $zip->getNameIndex($i);
                        $fileinfo = pathinfo($filename);
                        if (!isset($fileinfo['extension'])) {
                            continue;
                        }

                        $ext = $fileinfo['extension'];
                        // If the extension is invalid then skip the file.
                        if (
                            $ext === null || $ext === 'jpg' || $ext === 'pdf' || $ext === 'odt' ||
                            $ext === 'png' || $ext === 'docx' || $ext === 'exe' || $ext === 'pdb'
                        ) {
                            continue;
                        }

                        // If the extension is supported:
                        if (array_search($ext, $supported) !== false) {
                            // Increment the appropriate counters.
                            $langs = $this->moss->getExtensionLanguages($ext);
                            foreach ($langs as $lang) {
                                if (!isset($languageCounter[$lang])) {
                                    $languageCounter[$lang] = 0;
                                }
                                ++$languageCounter[$lang];
                            }

                            // Generate unique output file name.
                            $outFileName = $fileinfo['basename'];
                            $outFileIndex = 0;
                            while (file_exists($path . '/' . $outFileName)) {
                                ++$outFileIndex;
                                $outFileName = "{$fileinfo['filename']}_$outFileIndex.{$fileinfo['extension']}";
                            }

                            // Copy the file into the plagiarism validation folder.
                            copy(
                                'zip://' . $zipfile . '#' . $filename,
                                $path . '/' . $outFileName
                            );
                        }
                    }
                    // Close the zip.
                    $zip->close();
                } else {
                    Yii::warning("Failed to open '$zipfile': $res", __CLASS__);
                }
            }
        }

        if (empty($languageCounter)) {
            throw new BadRequestHttpException(Yii::t('app', 'Submissions contain no supported file formats.'));
        }

        if (!file_exists("{$this->plagiarismPath}/basefiles")) {
            mkdir("{$this->plagiarismPath}/basefiles", 0755);
        }
        foreach ($this->plagiarism->baseFiles as $baseFile) {
            copy($baseFile->path, "{$this->plagiarismPath}/basefiles/{$baseFile->id}");
        }

        // Set the validation language to the one which was present most often.
        arsort($languageCounter);
        reset($languageCounter);
        $lang = key($languageCounter);
        $this->moss->setLanguage($lang);

        return $lang;
    }

    protected function preProcess()
    {
        // Get the entry.
        $this->plagiarism = Plagiarism::findOne($this->plagiarismId);

        $this->moss->setIgnoreLimit($this->plagiarism->ignoreThreshold);

        $lang = $this->setupTemporaryFiles();

        // change CWD to plagiarism folder
        $this->cwd = getcwd();
        chdir($this->plagiarismPath);

        // Point to the plagiarism validation folder.
        $exts = $this->moss->getLanguageExtensions($lang);
        foreach ($exts as $ext) {
            $this->moss->addByWildcard('*/*/*.' . $ext);
        }
        // Add ascii files anyway
        $this->moss->addByWildcard('*/*/*.txt');
        $this->moss->addByWildcard('*/*/*.md');

        foreach ($this->plagiarism->baseFiles as $baseFile) {
            $this->moss->addBaseFile("basefiles/{$baseFile->id}");
        }

        // Use directory mode - specifies that submissions are by directory, not by file.
        $this->moss->setDirectoryMode(true);
    }

    protected function findPlagiarisms()
    {
        // Send the request.
        $this->mossUrl = trim($this->moss->send());
    }

    protected function postProcess()
    {

        //download moss
        $downloader = $this->getMossDownloader();

        $downloader->downloadPages();

        $this->plagiarism->response = $this->mossUrl;
        $this->plagiarism->token = $this->token;

        // Save the entry.
        $this->plagiarism->save();

        chdir($this->cwd);
        $this->deleteTemporaryFiles();
    }

    protected function deleteTemporaryFiles()
    {
        FileHelper::removeDirectory($this->plagiarismPath);
    }
}
