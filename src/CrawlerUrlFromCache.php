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
            $cachedContent = file_get_contents($filePath);
            if (0 === strpos($cachedContent, 'curl_error_code:')) {
                $this->harvest = substr($cachedContent, strlen('curl_error_code:'));
                if (42 != $this->harvest) {
                    $this->harvest = parent::getHarvester(); // retry if was not stopped because too big
                }
            } else {
                $response = new ResponseFromCache(
                    $filePath, // todo: push a PR on PiedWeb\Curl to permit to create ResponseFromCacheString
                    $this->config->getBase().$this->url->getUri(),
                    json_decode(file_get_contents($filePath.'---info'), true)
                );

                $this->harvest = new Harvest($response);
            }
        } else {
            $this->harvest = parent::getHarvester();
        }

        if ($this->harvest instanceof Harvest && null !== $this->config->getRobotsTxtCached()) {
            $this->harvest->setRobotsTxt($this->config->getRobotsTxtCached());
        }

        return $this->getHarvester();
    }
}
