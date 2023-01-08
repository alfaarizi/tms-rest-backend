<?php

namespace app\components\plagiarism;

use app\exceptions\PlagiarismServiceException;
use Yii;

class JPlagPlagiarismFinder extends AbstractPlagiarismFinder
{
    public static function isEnabled(): bool
    {
        return !empty(Yii::$app->params['jplag']['jar']);
    }

    protected function getExtensionLanguages(string $ext): ?array
    {
        static $supported = null;
        if ($supported === null) {
            $supported = JPlagOptions::getExtensionsLanguages();
        }
        return $supported[$ext] ?? null;
    }

    protected function setupTemporaryFiles(): void
    {
        parent::setupTemporaryFiles();

        if ($this->plagiarism->hasBaseFiles) {
            if (!file_exists("{$this->plagiarismPath}/basefiles")) {
                mkdir("{$this->plagiarismPath}/basefiles", 0755);
            }
            foreach ($this->plagiarism->baseFiles as $baseFile) {
                $ext = pathinfo($baseFile->name, PATHINFO_EXTENSION);
                copy($baseFile->path, "{$this->plagiarismPath}/basefiles/{$baseFile->id}.$ext");
            }
        }
    }

    private function getJPlagOptions(): JPlagOptions
    {
        $jplagPlagiarism = $this->plagiarism->jplag;
        $options = new JPlagOptions();
        $options->setRootDirectories(...glob("{$this->plagiarismPath}/[123456789]*/"));
        $options->setLanguage($this->lang);
        if ($jplagPlagiarism->tune > 0) {
            $options->setMinTokenMatch($jplagPlagiarism->tune);
        }
        $options->setResultDirectory(self::getResultDirectory($this->plagiarism->id) . '/result');
        if ($jplagPlagiarism->ignoreFiles !== '') {
            $excludeFile = $this->plagiarismPath . '/ignoreFiles.txt';
            file_put_contents($excludeFile, $jplagPlagiarism->ignoreFiles);
            $options->setExcludeFile($excludeFile);
        }
        if ($this->plagiarism->hasBaseFiles) {
            $options->setBaseCode("{$this->plagiarismPath}/basefiles");
        }
        return $options;
    }

    protected function getCommand(): string
    {
        $jplagConfig = Yii::$app->params['jplag'];
        return "{$jplagConfig['jre']} -jar {$jplagConfig['jar']} {$this->getJPlagOptions()}";
    }

    protected function findPlagiarisms(): void
    {
        $cmd = $this->getCommand();
        if (Yii::$app instanceof \yii\console\Application) {
            echo "Running JPlag command '$cmd'..." . PHP_EOL;
            // When running on the console, print everything directly on the console.
            // This means that the whole output is visible, and lines appear as they are printed,
            // not only after JPlag terminates.
            $result = passthru($cmd, $result_code);
            $output = [];
        } else {
            Yii::info("Running JPlag command '$cmd'...");
            $result = exec($cmd, $output, $result_code);
        }

        if ($result === false) {
            Yii::error([
                'msg' => 'JPlag command execution failure',
                'output' => $output,
            ]);
            throw new PlagiarismServiceException('Command execution failure');
        } elseif ($result_code !== 0) {
            if (!empty(array_filter($output, fn ($line) => stripos($line, 'Not enough valid submissions!')))) {
                Yii::warning([
                    'msg' => 'JPlag: not enough valid submissions',
                    'output' => $output,
                ]);
                throw new PlagiarismServiceException('Not enough valid submissions', $result_code);
            } else {
                Yii::error([
                    'msg' => 'JPlag command execution non-zero result code',
                    'output' => $output,
                ]);
                throw new PlagiarismServiceException('Non-zero result code', $result_code);
            }
        }
    }

    protected function postProcess(): void
    {
        $this->plagiarism->token = Yii::$app->security->generateRandomString();
        $this->plagiarism->save();
    }
}
