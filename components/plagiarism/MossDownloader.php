<?php

namespace app\components\plagiarism;

use Yii;
use yii\base\ErrorException;
use yii\helpers\FileHelper;
use Spatie\Crawler\Crawler;
use GuzzleHttp\HandlerStack;
use GuzzleRetry\GuzzleRetryMiddleware;

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

            // Retry on connection timeout and for 503 or 429 responses.
            $stack = HandlerStack::create();
            $stack->push(
                GuzzleRetryMiddleware::factory(
                    [
                        'retry_on_timeout' => true,
                        'max_retry_attempts' => 5
                    ]
                )
            );

            Crawler::create(['handler' => $stack])
                ->setCrawlObserver($observer)
                ->setCrawlProfile(new MossCrawlFilter())
                ->acceptNofollowLinks()
                ->ignoreRobots()
                ->setDelayBetweenRequests(50)
                ->startCrawling($this->url);
        } catch (ErrorException $e) {
            return false;
        }
        return true;
    }
}
