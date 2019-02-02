<?php

namespace PiedWeb\SeoPocketCrawler;

use Spatie\Robots\RobotsTxt;
use League\Csv\Reader;

class CrawlerContinue extends Crawler
{
    public function __construct(string $id, ?string $dataDirectoryBasePath = null)
    {
        $this->id = trim($id);
        $this->initDataDirectory($dataDirectoryBasePath);

        $configFilePath = $this->getDataFolder().'/config.json';
        if (!file_exists($configFilePath)) {
            throw new \Exception('previous crawl not found (config.json)');
        }
        $config = json_decode(file_get_contents($configFilePath), true);

        $this->ignore = new RobotsTxt($config['ignore']);
        $this->limit = $config['limit'];
        $this->userAgent = $config['userAgent'];
        $this->wait = $config['wait'];
        $this->base = $config['base'];

        $this->recorder = new Recorder($this->getDataFolder(), (int) $config['cacheMethod']);
        $this->loadFromPreviousCrawl($config['startUrl']);
    }

    protected function loadFromPreviousCrawl(string $startUrl)
    {
        $resultFilePath = $this->getDataFolder().'/index.csv';
        if (!file_exists($resultFilePath)) {
            throw new \Exception('previous crawl not found (index.csv)');
        }

        $csv = Reader::createFromPath($resultFilePath, 'r');
        $csv->setHeaderOffset(0);

        $records = $csv->getRecords();
        foreach ($records as $r) {
            $this->urls[$r['uri']] = new Url($this->base.$r['uri'], 0);
            foreach ($r as $k => $v) {
                if ('can_be_crawled' == $k && !empty($v)) {
                    $v = (bool) $v;
                }
                $this->urls[$r['uri']]->$k = $v;
            }
            if (!empty($r['can_be_crawled'])) {
                ++$this->counter;
            }
        }

        $this->currentClick = $r['click'] ?? 0;

        return $startUrl;
    }
}
