<?php

namespace app\components\plagiarism;

use Psr\Http\Message\UriInterface;
use Spatie\Crawler\CrawlProfile;

class MossCrawlFilter extends CrawlProfile
{
    /**
     * Determine if the given url should be crawled.
     */
    public function shouldCrawl(UriInterface $url): bool
    {
        if (strpos($url->getPath(), 'general') !== false) {
            return false;
        } else {
            return true;
        }
    }
}
