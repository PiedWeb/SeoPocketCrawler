<?php

namespace PiedWeb\SeoPocketCrawler;

use PiedWeb\UrlHarvester\Harvest;

class Crawler
{
    /**
     * @var int number of Urls we can crawled before saving (0 = autosaving disabled)
     */
    const AUTOSAVE = 500;

    /** @var string */
    protected $harvester = '\PiedWeb\SeoPocketCrawler\CrawlerUrl';

    protected $currentClick = 0;

    protected $counter = 0;

    protected $urls = [];

    /**
     * @var CrawlerConfig
     */
    protected $config;

    /** @var bool */
    protected $debug = true;

    public function __construct(
        string $startUrl,
        string $ignore,
        int $limit,
        string $userAgent,
        int $cacheMethod = Recorder::CACHE_ID,
        int $wait = 100000, // microSeconds !
        bool $debug = true,
        ?string $dataDirectory = null
    ) {
        $this->config = new CrawlerConfig($startUrl, $ignore, $limit, $userAgent, $cacheMethod, $wait, $dataDirectory);

        $this->urls[$this->config->getStartUrl()] = null;

        $this->config->recordConfig();

        $this->debug = $debug;
    }

    public function getConfig(): CrawlerConfig
    {
        return $this->config;
    }

    public function crawl()
    {
        $nothingUpdated = true;

        $this->printDebugInitCrawlLoop();

        foreach ($this->urls as $urlToParse => $url) {
            if (null !== $url && (false === $url->can_be_crawled || true === $url->can_be_crawled)) { // déjà crawlé
                continue;
            }
            // additionnal check not required ?!
            //elseif ($this->currentClick > $this->config->getLimit()) {
            //    break;
            //}

            $this->printDebugCrawlUrl($urlToParse);

            $nothingUpdated = false;
            ++$this->counter;

            if (null === $this->urls[$urlToParse]) {
                $url = $this->urls[$urlToParse] = new Url($this->config->getBase().$urlToParse, $this->currentClick);
            }

            if (false !== $this->canBeCrawled($url)) {
                $crawlerUrl = new $this->harvester($url, $this->config);

                if ($crawlerUrl->getHarvester() instanceof Harvest) {
                    $this->updateInboundLinksAndUrlsToParse($url, $crawlerUrl->getLinks());
                    $this->config->getRecorder()->recordLinksIndex(
                        $this->config->getBase(),
                        $url,
                        $this->urls,
                        $crawlerUrl->getHarvester()->getLinks()
                    );
                }

                $this->urls[$urlToParse]->setDiscovered(count($this->urls));

                $this->config->cacheRobotsTxt($crawlerUrl->getHarvester());
                $this->config->cacheRequest($crawlerUrl->getHarvester());

                usleep($this->config->getWait());
            }

            $this->autosave();
        }

        ++$this->currentClick;

        // Record after each Level:
        $this->config->getRecorder()->record($this->urls);

        $record = $nothingUpdated || $this->currentClick >= $this->config->getLimit();

        return $record ? null : $this->crawl();
    }

    protected function autosave()
    {
        if (0 !== $this->counter && $this->counter / self::AUTOSAVE == round($this->counter / self::AUTOSAVE)) {
            echo $this->debug ? '    --- auto-save'.PHP_EOL : '';
            $this->getRecorder()->record($this->urls);
        }
    }

    protected function canBeCrawled(Url $url)
    {
        if (null === $url->can_be_crawled) {
            $url->can_be_crawled = $this->config->getVirtualRobots()
            ->allows($this->config->getBase().$url->uri, $this->config->getUserAgent());
        }

        return $url->can_be_crawled;
    }

    public function updateInboundLinksAndUrlsToParse(Url $url, array $links)
    {
        $everAdd = [];
        foreach ($links as $link) {
            $newUri = $link->getUrl()->getRelativizedDocumentUrl();
            $this->urls[$newUri] = $this->urls[$newUri] ?? new Url($link->getPageUrl(), ($this->currentClick + 1));
            if (!isset($everAdd[$newUri])) {
                $everAdd[$newUri] = 1;
                if (!$link->mayFollow()) {
                    ++$this->urls[$newUri]->inboundlinks_nofollow;
                } else {
                    ++$this->urls[$newUri]->inboundlinks;
                }
                $this->getRecorder()->recordInboundLink($link, $url, $this->urls[$newUri]);
            }
        }
    }

    protected function printDebugCrawlUrl(string $urlToParse)
    {
        if ($this->debug) {
            echo $this->counter.'/'.count($this->urls).'    '.$this->config->getBase().$urlToParse.PHP_EOL;
        }
    }

    protected function printDebugInitCrawlLoop()
    {
        if ($this->debug) {
            echo PHP_EOL.PHP_EOL.'// -----'.PHP_EOL.'// '.$this->counter.' crawled / '
                        .count($this->urls).' found '.PHP_EOL.'// -----'.PHP_EOL;
        }
    }

    protected function getRecorder()
    {
        return $this->config->getRecorder();
    }
}
