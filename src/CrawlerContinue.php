<?php

namespace PiedWeb\SeoPocketCrawler;

class CrawlerContinue extends Crawler
{
    public function __construct(string $id, ?string $dataDirectory = null)
    {
        $this->config = CrawlerConfig::loadFrom($id, $dataDirectory);

        $this->recorder = new Recorder($this->config->getDataFolder(), $this->config->getCacheMethod());

        $dataFromPreviousCrawl = $this->config->getDataFromPreviousCrawl();

        foreach ($dataFromPreviousCrawl as $k => $v) {
            $this->$k = $v;
        }
    }
}
