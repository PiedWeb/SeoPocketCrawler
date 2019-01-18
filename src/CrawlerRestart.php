<?php

namespace PiedWeb\SeoPocketCrawler;

use PiedWeb\UrlHarvester\Harvest;
use PiedWeb\Curl\ResponseFromCache;

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

    protected function getHarvest(Url $url)
    {
        if (true === $this->fromCache) {
            $filePath = $this->recorder->getCacheFilePath($url);
            if ($filePath !== null && file_exists($filePath)) {
                $response = new ResponseFromCache(
                    $filePath,
                    $this->base.$url->uri,
                    json_decode(file_get_contents($filePath.'---info'), true)
                );

                return new Harvest($response);
            }
        }

        return parent::getHarvest($url);
    }
}
