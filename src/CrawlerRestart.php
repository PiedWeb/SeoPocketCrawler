<?php

namespace PiedWeb\SeoPocketCrawler;

class CrawlerRestart extends CrawlerContinue
{
    public function __construct(
        string $id,
        bool $fromCache = false,
        bool $debug = true,
        ?string $dataDirectory = null
    ) {
        if (true === $fromCache) {
            $this->harvester = '\PiedWeb\SeoPocketCrawler\CrawlerUrlFromCache';
        }

        $this->config = CrawlerConfig::loadFrom($id, $dataDirectory);

        $this->resetLinks();

        //$this->recorder = new Recorder($this->config->getDataFolder(), $this->config->getCacheMethod());

        $this->urls[$this->config->getStartUrl()] = null;

        $this->debug = $debug;
    }

    protected function resetLinks()
    {
        exec('rm -rf '.$this->config->getDataFolder().Recorder::LINKS_DIR);
    }
}
