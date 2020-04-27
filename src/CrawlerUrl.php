<?php

namespace PiedWeb\SeoPocketCrawler;

use PiedWeb\UrlHarvester\Harvest;
use PiedWeb\UrlHarvester\Indexable;
use PiedWeb\UrlHarvester\Link;

class CrawlerUrl
{
    /** @var mixed */
    protected $harvest;
    /** @var Url */
    protected $url;
    /** @var CrawlerConfig */
    protected $config;

    /** @var array internal links from the current Url */
    protected $links = [];

    public function __construct(Url $url, CrawlerConfig $config)
    {
        $this->url = $url;
        $this->config = $config;

        $this->harvest();
    }

    protected function harvest()
    {
        if ($this->isNetworkError()) {
            return null;
        }

        if ($this->isRedirection()) {
            return null;
        }

        $this->defaultHarvesting();
    }

    /**
     * permit to easily extend and change what is harvested, for example adding :
     * $this->harvestBreadcrumb();
     * $this->url->setKws(','.implode(',', array_keys($this->getHarvester()->getKws())).','); // Slow ~20%
     * $this->url->setRatioTextCode($this->getHarvester()->getRatioTxtCode()); // Slow ~30%
     * $this->url->setH1($this->getHarvester()->getUniqueTag('h1') ?? '');.
     */
    protected function defaultHarvesting()
    {
        $this->url->setIndexable($this->getHarvester()->indexable()); // slow ~30%

        $this->url->setMimeType((string) $this->getHarvester()->getResponse()->getMimeType());

        $this->harvestLinks();

        // Old way: $this->getHarvester()->getTextAnalysis()->getWordNumber();
        $this->url->setWordCount($this->getHarvester()->getWordCount());

        $this->url->setLoadTime($this->getHarvester()->getResponse()->getInfo('total_time'));

        $this->url->setSize($this->getHarvester()->getResponse()->getInfo('size_download'));

        $this->url->setTitle($this->getHarvester()->getUniqueTag('head title') ?? '');
    }

    protected function isNetworkError()
    {
        if (!$this->getHarvester() instanceof Harvest) {
            $this->url->setIndexable(
                $this->getHarvester() != 42 ? Indexable::NOT_INDEXABLE_NETWORK_ERROR : Indexable::NOT_INDEXABLE_TOO_BIG
            );
        $this->config->getRecorder()->cache($this->getHarvester(), $this->url);

            return true;
        }

        $this->config->getRecorder()->cache($this->getHarvester(), $this->url);

        return false;
    }

    protected function isRedirection()
    {
        if ($redir = $this->getHarvester()->getRedirectionLink()) {
            if ($redir->isInternalLink()) { // add to $links to permits to update counter & co
                $this->links[] = $redir;
            }
            $this->url->setIndexable(Indexable::NOT_INDEXABLE_3XX);

            return true;
        }

        return false;
    }

    protected function harvestLinks()
    {
        $this->config->getRecorder()->recordOutboundLink($this->url, $this->getHarvester()->getLinks()); // ~10%
        $this->url->links = count($this->getHarvester()->getLinks());
        $this->url->links_duplicate = $this->getHarvester()->getNbrDuplicateLinks();
        $this->url->links_internal = count($this->getHarvester()->getLinks(Link::LINK_INTERNAL));
        $this->url->links_self = count($this->getHarvester()->getLinks(Link::LINK_SELF));
        $this->url->links_sub = count($this->getHarvester()->getLinks(Link::LINK_SUB));
        $this->url->links_external = count($this->getHarvester()->getLinks(Link::LINK_EXTERNAL));
        $this->links = $this->getHarvester()->getLinks(Link::LINK_INTERNAL);
    }

    /**
     * @return int|Harvest int correspond to curl error
     */
    public function getHarvester()
    {
        if (null !== $this->harvest) {
            return $this->harvest;
        }

        $this->harvest = Harvest::fromUrl(
            $this->config->getBase().$this->url->getUri(),
            $this->config->getUserAgent(),
            'en,en-US;q=0.5',
            $this->config->getRequestCached()
        );

        if ($this->harvest instanceof Harvest && null !== $this->config->getRobotsTxtCached()) {
            $this->harvest->setRobotsTxt($this->config->getRobotsTxtCached());
        }

        return $this->getHarvester();
    }

    public function getLinks()
    {
        return $this->links;
    }

    /*
    protected function harvestBreadcrumb()
    {
        $breadcrumb = $this->getHarvester()->getBreadCrumb();
        if (is_array($breadcrumb)) {
            $this->url->setBreadcrumbLevel(count($breadcrumb));
            $this->url->setBreadcrumbFirst(isset($breadcrumb[1]) ? $breadcrumb[1]->getCleanName() : '');
            $this->url->setBreadcrumbText($this->getHarvester()->getBreadCrumb('//'));
        }
    }/**/
}
