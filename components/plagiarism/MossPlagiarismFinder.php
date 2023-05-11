<?php

namespace app\components\plagiarism;

use Yii;
use app\models\Plagiarism;
use yii\helpers\FileHelper;

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

    /** The third-party Moss handler */
    private Moss $moss;

    public static function isEnabled(): bool
    {
        return !empty(Yii::$app->params['mossId']);
    }

    /**
     * Constructor.
     * @param Plagiarism $plagiarism The plagiarism check to execute
     * @param Moss $moss The third-party Moss handler
     */
    public function __construct(Plagiarism $plagiarism, Moss $moss)
    {
        parent::__construct($plagiarism);
        $this->moss = $moss;
        $this->token = Yii::$app->security->generateRandomString();
    }

    protected function getExtensionLanguages(string $ext): ?array
    {
        if (in_array($ext, $this->moss->getAllowedExtensions(), true)) {
            return $this->moss->getExtensionLanguages($ext);
        } else {
            return null;
        }
    }

    protected function getStoredFileName(string $rootPath, string $filename): string
    {
        $pathinfo = pathinfo($filename);
        $outFileName = $pathinfo['basename'];
        $outFileIndex = 0;
        while (file_exists("$rootPath/$outFileName")) {
            ++$outFileIndex;
            $outFileName = "{$pathinfo['filename']}_$outFileIndex.{$pathinfo['extension']}";
        }
        return $outFileName;
    }

    protected function setupTemporaryFiles(): void
    {
        parent::setupTemporaryFiles();

        if (!file_exists("{$this->plagiarismPath}/basefiles")) {
            FileHelper::createDirectory("{$this->plagiarismPath}/basefiles", 0755);
        }
        foreach ($this->plagiarism->baseFiles as $baseFile) {
            copy($baseFile->path, "{$this->plagiarismPath}/basefiles/{$baseFile->id}");
        }
    }

    protected function preProcess(): void
    {
        $this->setupTemporaryFiles();

        $this->moss->setLanguage($this->lang);
        $this->moss->setIgnoreLimit($this->plagiarism->moss->ignoreThreshold);

        // change CWD to plagiarism folder
        $this->cwd = getcwd();
        chdir($this->plagiarismPath);

        // Point to the plagiarism validation folder.
        $exts = $this->moss->getLanguageExtensions($this->lang);
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

    protected function findPlagiarisms(): void
    {
        // Send the request.
        $this->mossUrl = trim($this->moss->send());
    }

    protected function postProcess(): void
    {
        //download moss
        Yii::$container->get(MossDownloader::class, [
            $this->plagiarism->id,
            $this->token,
            $this->mossUrl,
        ])->downloadPages();

        $this->plagiarism->moss->response = $this->mossUrl;
        $this->plagiarism->token = $this->token;

        // Save the entries.
        $transaction = Plagiarism::getDb()->beginTransaction();
        try {
            if ($this->plagiarism->save() && $this->plagiarism->moss->save()) {
                $transaction->commit();
            } else {
                $transaction->rollBack();
            }
        } catch (\Throwable $t) {
            $transaction->rollBack();
            throw $t;
        }

        chdir($this->cwd);
        $this->deleteTemporaryFiles();
    }
}
