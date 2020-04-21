<?php

namespace PiedWeb\SeoPocketCrawler;

use PiedWeb\Curl\ResponseFromCache;
use PiedWeb\UrlHarvester\Harvest;

class CrawlerUrlFromCache extends CrawlerUrl
{
    public function getHarvester()
    {
        if (null !== $this->harvest) {
            return $this->harvest;
        }

        $filePath = $this->config->getRecorder()->getCacheFilePath($this->url);
        if (null !== $filePath && file_exists($filePath)) {
            $response = new ResponseFromCache(
                $filePath,
                $this->config->getBase().$this->url->getUri(),
                json_decode(file_get_contents($filePath.'---info'), true)
            );

            $this->harvest = new Harvest($response);
        }

        $this->harvest ?? $this->harvest = parent::getHarvester();

        if (null !== $this->config->getRobotsTxtCached()) {
            $this->harvest->setRobotsTxt($this->config->getRobotsTxtCached());
        }

        return $this->harvest;
    }
}
