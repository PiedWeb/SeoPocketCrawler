<?php

namespace PiedWeb\SeoPocketCrawler;

use PiedWeb\Curl\ResponseFromCache;
use PiedWeb\UrlHarvester\Harvest;

class CrawlerUrlFromCache extends CrawlerUrl
{
    public function getHarvester(): ?Harvest
    {
        if (null !== $this->harvest) {
            return false === $this->harvest ? null : $this->harvest;
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

        if (!$this->harvest instanceof Harvest) {
            $this->harvest = false;
        }

        if (null !== $this->getHarvester() && null !== $this->config->getRobotsTxtCached()) {
            $this->harvest->setRobotsTxt($this->config->getRobotsTxtCached());
        }

        return $this->getHarvester();
    }
}
