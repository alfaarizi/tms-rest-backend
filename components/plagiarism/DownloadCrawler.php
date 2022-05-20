<?php

namespace app\components\plagiarism;

use DOMDocument;
use DOMElement;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use Yii;
use yii\base\ErrorException;

class DownloadCrawler extends \Spatie\Crawler\CrawlObserver
{
    private int $plagiarismId;
    private string $ids;
    private string $dirPath;
    private int $count;

    private const STANFORD_LINK_BASE_REGEX = 'http://moss.stanford.edu/results/.*/match';
    private const STANFORD_LINK_BASE_REGEX_ABSOLUTE = '~^' . DownloadCrawler::STANFORD_LINK_BASE_REGEX . '~';
    private const STANFORD_LINK_BASE_REGEX_BOTH = '~^(' . DownloadCrawler::STANFORD_LINK_BASE_REGEX . '|match)~';

    public function __construct(int $id, string $token, string $dirPath)
    {
        $this->plagiarismId = $id;
        $this->ids = "?id=$id&token=$token";
        $this->dirPath = $dirPath;
        $this->count = 0;
    }

    /**
     * Called when the crawler has crawled the given URL successfully.
     */
    public function crawled(
        UriInterface $url,
        ResponseInterface $response,
        ?UriInterface $foundOnUrl = null
    ) {
        $host = $url->getHost();
        $scheme = $url->getScheme();
        $path = $url->getPath();

        $linkPrefix = $scheme . '://' . $host;

        if (is_int(stripos($path, 'match'))) {
            $basePath = explode('.', $path)[0];

            $linkPrefix = $linkPrefix . $basePath;

            $fileName = explode('/', $basePath);
            $fileName = $fileName[count($fileName) - 1];


            $topPostfix = '-top.html';
            $leftPostfix = '-0.html';
            $rightPostfix = '-1.html';

            $this->downloadPageWithRefactor($fileName, $linkPrefix, $topPostfix, false);

            $this->downloadPageWithRefactor($fileName, $linkPrefix, $leftPostfix, false);

            $this->downloadPageWithRefactor($fileName, $linkPrefix, $rightPostfix, false);
            $this->count += 1;
        } else {
            $linkPrefix = $linkPrefix . $path;
            $this->downloadPageWithRefactor('index.html', $linkPrefix, '', true);
        }
    }

    /**
     * Called when the crawler had a page; download it and refactor URLs.
     */
    private function downloadPageWithRefactor(
        string $fileName,
        string $linkPrefix,
        string $linkPostfix,
        bool $isBase
    ) {
        $dom = new DOMDocument();
        // Moss’ HTML output is quite broken, ignore any error and warning messages
        // and let’s hope that it’s not unrecoverably broken
        if (!$dom->loadHTMLFile($linkPrefix . $linkPostfix, \LIBXML_NOERROR | \LIBXML_NOWARNING)) {
            throw new ErrorException('Download error!');
        }

        /** @var DOMElement $anchor */
        foreach ($dom->getElementsByTagName('a') as $anchor) {
            $href = $anchor->getAttribute('href');
            if ($isBase) {
                $href = $this->refactorBasePageAnchor($href);
            } else {
                $href = $this->refactorSidePageAnchor($href);
            }
            $anchor->setAttribute('href', $href);
        }

        $dom->saveHTMLFile($this->dirPath . '/' . $fileName . $linkPostfix);
    }

    /**
     * Fix an anchor’s (`<a>` element’s) href on the index page.
     * @param string $href The href to fix
     * @return string The fixed href
     */
    private function refactorBasePageAnchor(string $href): string
    {
        if (preg_match(DownloadCrawler::STANFORD_LINK_BASE_REGEX_ABSOLUTE, $href)) {
            $href = preg_replace(
                DownloadCrawler::STANFORD_LINK_BASE_REGEX_ABSOLUTE,
                "./plagiarism-result/match{$this->ids}&number=",
                $href
            );
            $href = preg_replace('/\.html/', '', $href);
        }
        return $href;
    }

    /**
     * Fix an anchor’s (`<a>` element’s) href on a non-index page.
     * @param string $href The href to fix
     * @return string The fixed href
     */
    private function refactorSidePageAnchor(string $href): string
    {
        if (preg_match(DownloadCrawler::STANFORD_LINK_BASE_REGEX_BOTH, $href)) {
            $href = preg_replace(
                DownloadCrawler::STANFORD_LINK_BASE_REGEX_BOTH,
                "./frame{$this->ids}&number={$this->count}&side=",
                $href
            );
            $href = preg_replace('/&side=.-1/', '&side=1', $href);
            $href = preg_replace('/&side=.-0/', '&side=0', $href);
            $href = preg_replace('/\.html/', '', $href);
        }
        return $href;
    }

    /**
     * Called when the crawler had a problem crawling the given url.
     */
    public function crawlFailed(
        UriInterface $url,
        RequestException $requestException,
        ?UriInterface $foundOnUrl = null
    ) {
    }
}
