<?php

namespace PiedWeb\SeoPocketCrawler;

use PiedWeb\UrlHarvester\Harvest;
use PiedWeb\UrlHarvester\Indexable;
use Spatie\Robots\RobotsTxt;

class Crawler
{
    /**
     * @var string contain the user agent used during the crawl
     */
    protected $userAgent;

    /**
     * @var string crawl id
     */
    protected $id;

    /**
     * @var RobotsTxt page to ignore during the crawl
     */
    protected $ignore;

    /**
     * @var int depth max where to crawl
     */
    protected $limit;

    /**
     * @var string contain https://domain.tdl from start url
     */
    protected $base;

    /**
     * @var bool
     */
    protected $fromCache;

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
        int $cacheMethod = Recorder::CACHE_ID,
        int $waitInMicroSeconds = 100000
    ) {
        $startUrl = $this->setBaseAndReturnNormalizedStartUrl($startUrl);
        $this->urls[$startUrl] = null;
        $this->id = date('ymdHi').'-'.parse_url($this->base, PHP_URL_HOST);
        $this->ignore = new RobotsTxt($ignore);
        $this->userAgent = $userAgent;
        $this->limit = $limit;
        $this->wait = $waitInMicroSeconds;

        $this->recorder = new Recorder($this->getDataFolder(), $cacheMethod);

        file_put_contents($this->getDataFolder().'/config.json', json_encode([
            'startUrl' => $startUrl,
            'base' => $this->base,
            'ignore' => $ignore,
            'limit' => $limit,
            'userAgent' => $userAgent,
            'cacheMethod' => $cacheMethod,
            'wait' => $waitInMicroSeconds,
        ]));
    }

    public function getId()
    {
        return $this->id;
    }

    protected function setBaseAndReturnNormalizedStartUrl(string $url): string
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new \Exception('start is not a valid URL `'.$url.'`');
        }

        $this->base = preg_match('@^(http://|https://)?[^/\?#]+@', $url, $match) ? $match[0] : $url;
        $url = substr($url, strlen($this->base));

        return ('/' != $url[0] ? '/' : '').$url;
    }

    public function getDataFolder()
    {
        return __DIR__.'/../data/'.$this->id;
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
            } elseif ($this->currentClick > $this->limit) {
                continue;
            }

            if ($debug) {
                echo $this->counter.'/'.count($this->urls).'    '.$this->base.$urlToParse.PHP_EOL;
            }

            $nothingUpdated = false;
            ++$this->counter;

            $harvest = $this->harvest($urlToParse);
            $this->urls[$urlToParse]->setDiscovered(count($this->urls));

            $this->cacheRobotsTxt($harvest);

            $this->cacheRequest($harvest);

            usleep($this->wait);

            if ($this->counter / 500 == round($this->counter / 500)) {
                echo $debug ? '    --- auto-save'.PHP_EOL : '';
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

    protected function getHarvest(Url $url)
    {
        return Harvest::fromUrl(
            $this->base.$url->uri,
            $this->userAgent,
            'en,en-US;q=0.5',
            $this->request
        );
    }

    protected function harvest(string $urlToParse)
    {
        $this->urls[$urlToParse] = $this->urls[$urlToParse] ?? new Url($this->base.$urlToParse, $this->currentClick);
        $url = $this->urls[$urlToParse];

        $url->can_be_crawled = $this->ignore->allows($this->base.$urlToParse, $this->userAgent);

        if (false === $url->can_be_crawled) {
            return;
        }

        $harvest = $this->getHarvest($url);

        if (!$harvest instanceof Harvest) {
            $url->indexable = Indexable::NOT_INDEXABLE_NETWORK_ERROR;

            return;
        }

        $this->loadRobotsTxt($harvest);

        $url->indexable = $harvest->isIndexable(); // slow ~30%

        if (Indexable::NOT_INDEXABLE_3XX === $url->indexable) {
            $redir = $harvest->getRedirection();
            if (false !== $redir) {
                $links = Harvest::LINK_INTERNAL === $harvest->getType($redir) ? [$redir] : [];
            }
        } else {
            $this->recorder->cache($harvest, $url);

            $mimeType = $harvest->getResponse()->getMimeType();
            $url->mime_type = 'text/html' == $mimeType ? 1 : $mimeType;

            $this->recorder->recordOutboundLink($url, $harvest->getLinks()); // ~10%

            $url->links = count($harvest->getLinks());
            $url->links_duplicate = $harvest->getNbrDuplicateLinks();
            $url->links_internal = count($harvest->getLinks(Harvest::LINK_INTERNAL));
            $url->links_self = count($harvest->getLinks(Harvest::LINK_SELF));
            $url->links_sub = count($harvest->getLinks(Harvest::LINK_SUB));
            $url->links_external = count($harvest->getLinks(Harvest::LINK_EXTERNAL));
            $links = $harvest->getLinks(Harvest::LINK_INTERNAL);

            //$url->ratio_text_code = $harvest->getRatioTxtCode(); // Slow ~30%
            $url->words_count = $harvest->getTextAnalysis()->getWordNumber();
            $url->load_time = $harvest->getResponse()->getInfo('total_time');
            $url->size = $harvest->getResponse()->getInfo('size_download');

            $breadcrumb = $harvest->getBreadCrumb();
            if (is_array($breadcrumb)) {
                $url->breadcrumb_level = count($breadcrumb);
                $url->breadcrumb_first = isset($breadcrumb[1]) ? $breadcrumb[1]->getCleanName() : '';
                $url->breadcrumb_text = $harvest->getBreadCrumb('//');
            }

            $url->title = $harvest->getUniqueTag('head title') ?? '';
            $url->kws = ','.implode(',', array_keys($harvest->getKws())).','; // Slow ~20%
            $url->h1 = $harvest->getUniqueTag('h1') ?? '';
            $url->h1 = $url->title == $url->h1 ? '=' : $url->h1;
        }

        $everAdd = [];
        if (isset($links)) {
            foreach ($links as $link) {
                $newUri = substr($link->getPageUrl(), strlen($this->base));
                $this->urls[$newUri] = $this->urls[$newUri] ?? new Url($link->getPageUrl(), ($this->currentClick + 1));
                if (!isset($everAdd[$newUri])) {
                    $everAdd[$newUri] = 1;
                    $this->recorder->recordInboundLink($url, $this->urls[$newUri]);
                    ++$this->urls[$newUri]->inboundlinks;
                }
            }
        }

        return $harvest;
    }
}
