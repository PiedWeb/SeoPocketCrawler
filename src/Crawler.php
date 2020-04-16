<?php

namespace PiedWeb\SeoPocketCrawler;

use PiedWeb\UrlHarvester\Harvest;
use PiedWeb\UrlHarvester\Indexable;
use PiedWeb\UrlHarvester\Link;

class Crawler
{
    const FOLLOW = 1;
    const NOFOLLOW = 2;

    protected $recorder;

    protected $robotsTxt;
    protected $request;

    protected $currentClick = 0;

    protected $counter = 0;

    protected $urls = [];

    /**
     * @var CrawlerConfig
     */
    protected $config;

    public function __construct(
        string $startUrl,
        string $ignore,
        int $limit,
        string $userAgent,
        int $cacheMethod = Recorder::CACHE_ID,
        int $wait = 100000, // microSeconds !
        ?string $dataDirectory = null
    ) {
        $this->config = new CrawlerConfig($startUrl, $ignore, $limit, $userAgent, $cacheMethod, $wait, $dataDirectory);

        $this->urls[$this->config->getStartUrl()] = null;

        $this->recorder = new Recorder($this->config->getDataFolder(), $this->config->getCacheMethod());

        $this->config->recordConfig();
    }

    public function getConfig(): CrawlerConfig
    {
        return $this->config;
    }

    public function crawl(bool $debug = false)
    {
        $nothingUpdated = true;

        if ($debug) {
            echo PHP_EOL.PHP_EOL.'// -----'.PHP_EOL.'// '.$this->counter.' crawled / '
                        .count($this->urls).' found '.PHP_EOL.'// -----'.PHP_EOL;
        }

        foreach ($this->urls as $urlToParse => $url) {
            if (null !== $url && (false === $url->can_be_crawled || true === $url->can_be_crawled)) { // déjà crawlé
                continue;
            } elseif ($this->currentClick > $this->config->getLimit()) {
                continue;
            }

            if ($debug) {
                echo $this->counter.'/'.count($this->urls).'    '.$this->config->getBase().$urlToParse.PHP_EOL;
            }

            $nothingUpdated = false;
            ++$this->counter;

            if (null === $this->urls[$urlToParse]) {
                $url = $this->urls[$urlToParse] = new Url($this->config->getBase().$urlToParse, $this->currentClick);
            }

            $harvest = false === $this->canBeCrawled($url) ? null : $this->harvest($url);
            $this->urls[$urlToParse]->setDiscovered(count($this->urls));

            $this->cacheRobotsTxt($harvest);

            $this->cacheRequest($harvest);

            usleep($this->config->getWait());

            if ($this->counter / 500 == round($this->counter / 500)) {
                echo $debug ? '    --- auto-save'.PHP_EOL : '';
                $this->recorder->record($this->urls);
            }
        }

        ++$this->currentClick;

        // Record after each Level:
        $this->recorder->record($this->urls);

        $record = $nothingUpdated || $this->currentClick >= $this->config->getLimit();

        return $record ? null : $this->crawl($debug);
    }

    protected function cacheRobotsTxt($harvest)
    {
        if (null === $this->robotsTxt && $harvest instanceof Harvest) {
            $this->robotsTxt = $harvest->getRobotsTxt();
        }

        return $this;
    }

    protected function cacheRequest($harvest)
    {
        if ($harvest instanceof Harvest && null !== $harvest->getResponse()->getRequest()) {
            $this->request = $harvest->getResponse()->getRequest();
        }

        return $this;
    }

    protected function loadRobotsTxt(Harvest $harvest)
    {
        if (null !== $this->robotsTxt) {
            $harvest->setRobotsTxt($this->robotsTxt);
        }

        return $this;
    }

    protected function getHarvester(Url $url)
    {
        return Harvest::fromUrl(
            $this->config->getBase().$url->uri,
            $this->config->getUserAgent(),
            'en,en-US;q=0.5',
            $this->request
        );
    }

    protected function canBeCrawled(Url $url)
    {
        if (null === $url->can_be_crawled) {
            $url->can_be_crawled = $this->config->getVirtualRobots()
            ->allows($this->config->getBase().$url->uri, $this->config->getUserAgent());
        }

        return $url->can_be_crawled;
    }

    protected function harvest(Url $url): ?Harvest
    {
        $harvest = $this->getHarvester($url);

        if (!$harvest instanceof Harvest) {
            $url->indexable = Indexable::NOT_INDEXABLE_NETWORK_ERROR;

            return null;
        }

        $this->loadRobotsTxt($harvest);

        $url->indexable = $harvest->isIndexable(); // slow ~30%

        if (Indexable::NOT_INDEXABLE_3XX === $url->indexable) {
            $redir = $harvest->getRedirectionLink();
            if (null !== $redir && $redir->isInternalLink()) { // add to $links to permits to update counter & co
                $links = [$redir];
            }
        } else {
            $this->recorder->cache($harvest, $url);

            $mimeType = $harvest->getResponse()->getMimeType();
            $url->mime_type = 'text/html' == $mimeType ? 1 : $mimeType;

            $this->recorder->recordOutboundLink($url, $harvest->getLinks()); // ~10%
            $url->links = count($harvest->getLinks());
            $url->links_duplicate = $harvest->getNbrDuplicateLinks();
            $url->links_internal = count($harvest->getLinks(Link::LINK_INTERNAL));
            $url->links_self = count($harvest->getLinks(Link::LINK_SELF));
            $url->links_sub = count($harvest->getLinks(Link::LINK_SUB));
            $url->links_external = count($harvest->getLinks(Link::LINK_EXTERNAL));
            $links = $harvest->getLinks(Link::LINK_INTERNAL);

            //$url->ratio_text_code = $harvest->getRatioTxtCode(); // Slow ~30%
            $url->words_count = $harvest->getTextAnalysis()->getWordNumber();
            $url->load_time = $harvest->getResponse()->getInfo('total_time');
            $url->size = $harvest->getResponse()->getInfo('size_download');

            /*
             * I remove it from the default crawler, you can extend this one and restablish this code
             *
            $breadcrumb = $harvest->getBreadCrumb();
            if (is_array($breadcrumb)) {
                $url->breadcrumb_level = count($breadcrumb);
                $url->breadcrumb_first = isset($breadcrumb[1]) ? $breadcrumb[1]->getCleanName() : '';
                $url->breadcrumb_text = $harvest->getBreadCrumb('//');
            }
            *
            $url->kws = ','.implode(',', array_keys($harvest->getKws())).','; // Slow ~20%
            /**/

            $url->title = $harvest->getUniqueTag('head title') ?? '';
            $url->h1 = $harvest->getUniqueTag('h1') ?? '';
            $url->h1 = $url->title == $url->h1 ? '=' : $url->h1;
        }

        if (isset($links)) {
            $this->updateInboundLinksCounter($url, $links, $harvest);
            $this->recorder->recordLinksIndex($this->config->getBase(), $url, $this->urls, $harvest->getLinks());
        }

        return $harvest;
    }

    public function updateInboundLinksCounter(Url $url, array $links, Harvest $harvest)
    {
        $everAdd = [];
        foreach ($links as $link) {
            $newUri = substr($link->getPageUrl(), strlen($this->config->getBase()));
            $this->urls[$newUri] = $this->urls[$newUri] ?? new Url($link->getPageUrl(), ($this->currentClick + 1));
            if (!isset($everAdd[$newUri])) {
                $everAdd[$newUri] = 1;
                if (!$link->mayFollow() || !$harvest->mayFollow()) {
                    ++$this->urls[$newUri]->inboundlinks_nofollow;
                    $this->recorder->recordInboundLink($url, $this->urls[$newUri], self::NOFOLLOW);
                } else {
                    ++$this->urls[$newUri]->inboundlinks;
                    $this->recorder->recordInboundLink($url, $this->urls[$newUri], self::FOLLOW);
                }
            }
        }
    }
}
