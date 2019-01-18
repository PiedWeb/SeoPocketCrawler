<?php

namespace PiedWeb\SeoPocketCrawler;

use PiedWeb\UrlHarvester\Harvest;
use PiedWeb\UrlHarvester\Indexable;
use Spatie\Robots\RobotsTxt;

class Crawler
{
    protected $userAgent;
    protected $project;
    protected $ignore;
    protected $limit;
    protected $recorder;
    protected $robotsTxt;
    protected $request;
    protected $wait = 0;

    protected $currentClick = 0;

    protected $counter = 0;

    protected $urls = [];

    public function __construct(
        string $startUrl,
        string $ignore,
        int $limit,
        string $userAgent,
        int $cacheMethod = Recorder::CACHE_ID
    ) {
        $this->urls[$startUrl] = null;
        $this->project = preg_replace("([^\w\s\d\-_~,;\[\]\(\).])", '', $startUrl).'-'.date('ymd-Hi');
        $this->ignore = new RobotsTxt($ignore);
        $this->userAgent = $userAgent;
        $this->limit = $limit;

        $this->recorder = new Recorder($this->getDataFolder(), $cacheMethod);
    }

    public function getDataFolder()
    {
        return __DIR__.'/../data/'.$this->project;
    }

    public function setWaitBetweenRequest(int $microSeconds = 100000)
    {
        $this->wait = $microSeconds;
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
            }

            if ($debug) {
                echo $this->counter.'/'.count($this->urls).'    '.$urlToParse.PHP_EOL;
            }

            $nothingUpdated = false;
            ++$this->counter;

            $harvest = $this->harvest($urlToParse);
            $this->urls[$urlToParse]->setDiscovered(count($this->urls));

            $this->cacheRobotsTxt($harvest);

            $this->cacheRequest($harvest);

            usleep($this->wait);

            if ($this->counter / 500 == round($this->counter / 500)) {
                echo $debug ? '    --- aut-save'.PHP_EOL : '';
                $this->recorder->record($this->urls);
            }
        }

        ++$this->currentClick;

        // Record after each Level:
        $this->recorder->record($this->urls);

        $record = $nothingUpdated || $this->currentClick >= $this->limit;

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
        if ($harvest instanceof Harvest) {
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

    protected function harvest(string $urlToParse)
    {
        $url = $this->urls[$urlToParse] = $this->urls[$urlToParse] ?? new Url($urlToParse, $this->currentClick);

        $url->updated_at = date('Ymd');
        $url->can_be_crawled = $this->ignore->allows($urlToParse, $this->userAgent);

        if (false === $url->can_be_crawled) {
            return;
        }

        $harvest = Harvest::fromUrl(
            $urlToParse,
            $this->userAgent,
            'en,en-US;q=0.5',
            $this->request
        );

        if (!$harvest instanceof Harvest) {
            $url->indexable = Indexable::NOT_INDEXABLE_NETWORK_ERROR;

            return;
        }

        $this->loadRobotsTxt($harvest);

        $url->indexable = $harvest->isIndexable();

        if (Indexable::NOT_INDEXABLE_3XX === $url->indexable) {
            $redir = $harvest->getRedirection();
            if (false !== $redir) {
                $links = Harvest::LINK_INTERNAL === $harvest->getType($redir) ? [$redir] : [];
            }
        } else {
            $this->recorder->cache($harvest, $url);

            $mimeType = $harvest->getResponse()->getMimeType();
            $url->mime_type = 'text/html' == $mimeType ? 1 : $mimeType;

            $this->recorder->recordOutboundLink($url, $harvest->getLinks());

            $url->links = count($harvest->getLinks());
            $url->links_duplicate = $harvest->getNbrDuplicateLinks();
            $url->links_internal = count($harvest->getLinks(Harvest::LINK_INTERNAL));
            $url->links_self = count($harvest->getLinks(Harvest::LINK_SELF));
            $url->links_sub = count($harvest->getLinks(Harvest::LINK_SUB));
            $url->links_external = count($harvest->getLinks(Harvest::LINK_EXTERNAL));
            $links = $harvest->getLinks(Harvest::LINK_INTERNAL);

            $url->ratio_text_code = $harvest->getRatioTxtCode();
            $url->load_time = $harvest->getResponse()->getInfo('total_time');
            $url->size = $harvest->getResponse()->getInfo('size_download');

            $breadcrumb = $harvest->getBreadCrumb();
            if (is_array($breadcrumb)) {
                $url->breadcrumb_level = count($breadcrumb);
                $url->breadcrumb_first = isset($breadcrumb[1]) ? $breadcrumb[1]->getCleanName() : '';
                $url->breadcrumb_text = $harvest->getBreadCrumb('//');
            }

            $url->title = $harvest->getUniqueTag('head title') ?? '';
            $url->kws = ','.implode(',', array_keys($harvest->getKws())).',';
            $url->h1 = $harvest->getUniqueTag('h1') ?? '';
            $url->h1 = $url->title == $url->h1 ? '=' : $url->h1;
        }

        if (isset($links)) {
            foreach ($links as $link) {
                $linkUrl = $link->getPageUrl();
                $this->urls[$linkUrl] = $this->urls[$linkUrl] ?? new Url($linkUrl, ($this->currentClick + 1));
                $this->recorder->recordInboundLink($url, $this->urls[$linkUrl]);
                ++$this->urls[$linkUrl]->inboundlinks;
            }
        }

        return $harvest;
    }
}
