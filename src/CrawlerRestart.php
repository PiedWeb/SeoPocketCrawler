<?php

namespace PiedWeb\SeoPocketCrawler;

class CrawlerRestart extends CrawlerContinue
{
    public function __construct(string $id, bool $fromCache = false)
    {
        $this->fromCache = $fromCache;

        parent::__construct($id);
    }

    protected function loadFromPreviousCrawl(string $startUrl)
    {
        $this->urls[$startUrl] = null;
    }
}
