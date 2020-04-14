<?php

namespace PiedWeb\SeoPocketCrawler;

use PiedWeb\Curl\ResponseFromCache;
use PiedWeb\UrlHarvester\Harvest;

class CrawlerRestart extends CrawlerContinue
{
    /**
     * @var bool
     */
    protected $fromCache;

    public function __construct(string $id, bool $fromCache = false, ?string $dataDirectory = null)
    {
        $this->fromCache = $fromCache;

        $this->config = CrawlerConfig::loadFrom($id, $dataDirectory);

        $this->recorder = new Recorder($this->config->getDataFolder(), $this->config->getCacheMethod());

        $this->resetLinks();

        $this->urls[$this->config->getStartUrl()] = null;
    }

    protected function resetLinks()
    {
        exec('rm -rf '.$this->config->getDataFolder().Recorder::LINKS_DIR);
        mkdir($this->config->getDataFolder().Recorder::LINKS_DIR);
    }

    protected function getHarvester(Url $url)
    {
        if (false === $this->fromCache) {
            return parent::getHarvester($url);
        }

        $filePath = $this->recorder->getCacheFilePath($url);
        if (null !== $filePath && file_exists($filePath)) {
            $response = new ResponseFromCache(
                $filePath,
                $this->config->getBase().$url->uri,
                json_decode(file_get_contents($filePath.'---info'), true)
            );

            return new Harvest($response);
        }
    }
}
