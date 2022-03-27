<?php

namespace app\components\plagiarism;

use Yii;
use yii\base\ErrorException;
use yii\helpers\FileHelper;
use Spatie\Crawler\Crawler;

class MossDownloader
{
    private int $plagiarismId;
    private string $plagiarismToken;
    private string $url;
    private string $dirPath;

    public static function getPlagiarismDir(int $plagiarismId): string
    {
        $basePath =  Yii::$app->basePath . '/' . Yii::$app->params['data_dir'] . '/plagiarism/plagiarism-result';
        if (!is_dir($basePath)) {
            mkdir($basePath, 0755, false);
        }
        return "$basePath/$plagiarismId";
    }

    public function __construct(int $plagiarismId, string $plagiarismToken, string $url)
    {
        $this->plagiarismId = $plagiarismId;
        $this->plagiarismToken = $plagiarismToken;
        $this->url = $url;
        $this->dirPath = static::getPlagiarismDir($plagiarismId);

        if (is_dir($this->dirPath)) {
            FileHelper::removeDirectory($this->dirPath);
        }
        mkdir($this->dirPath, 0755, false);
    }

    /**
     * Call when need to download the pages without graph.
     */
    public function downloadPages()
    {
        try {
            $observer = new DownloadCrawler(
                $this->plagiarismId,
                $this->plagiarismToken,
                $this->dirPath,
            );
            Crawler::create()
                ->setCrawlObserver($observer)
                ->setCrawlProfile(new MossCrawlFilter())
                ->acceptNofollowLinks()
                ->ignoreRobots()
                ->startCrawling($this->url);
        } catch (ErrorException $e) {
            return false;
        }
        return true;
    }
}
