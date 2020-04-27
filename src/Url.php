<?php

namespace PiedWeb\SeoPocketCrawler;

use League\Uri\Http;
use League\Uri\UriInfo;

class Url
{
    private static $autoIncrement = 1;

    public $id;
    public $discovered;
    public $uri;
    public $click;
    public $pagerank;
    public $inboundlinks;
    public $inboundlinks_nofollow = 0;
    public $can_be_crawled;
    public $indexable;
    public $mime_type;
    public $links;
    public $links_duplicate;
    public $links_self;
    public $links_internal;
    public $links_sub;
    public $links_external;
    public $word_count;
    public $load_time;
    public $size;
    public $title;
    public $updated_at;
    //public $kws;
    //public $h1;
    //public $breadcrumb_level;
    //public $breadcrumb_first;
    //public $breadcrumb_text;

    public function __construct($url, $click = null)
    {
        $this->id = $this->getId();
        $this->uri = substr($url, strlen(UriInfo::getOrigin(Http::createFromString($url)))); //!
        $this->updated_at = date('Ymd');
        $this->inboundlinks = 0;
        $this->click = $click;
    }

    public function getId()
    {
        if (null === $this->id) {
            $this->id = self::$autoIncrement;
            ++self::$autoIncrement;
        }

        return $this->id;
    }

    public function setDiscovered(int $discovered)
    {
        $this->discovered = $discovered;

        return $this;
    }

    public function setMimeType(string $mimeType)
    {
        $this->mime_type = 'text/html' == $mimeType ? 1 : $mimeType;

        return $this;
    }

    /**
    public function setH1(string $h1)
    {
        $this->h1 = $this->title == $h1 ? '=' : $h1;

        return $this;
    }/**/
    public function setId($id)
    {
        $this->id = intval($id);
    }

    public function getDiscovered()
    {
        return $this->discovered;
    }

    public function getUri()
    {
        return $this->uri;
    }

    public function setUri($uri)
    {
        $this->uri = $uri;
    }

    public function getClick()
    {
        return $this->click;
    }

    public function setClick($click)
    {
        $this->click = (int) $click;
    }

    public function getPagerank()
    {
        return $this->pagerank;
    }

    public function setPagerank($pagerank)
    {
        $this->pagerank = (float) $pagerank;
    }

    public function getInboundlinks()
    {
        return $this->inboundlinks;
    }

    public function setInboundlinks($inboundlinks)
    {
        $this->inboundlinks = (int) $inboundlinks;
    }

    public function getInboundlinksNofollow()
    {
        return $this->inboundlinks_nofollow;
    }

    public function setInboundlinksNofollow($inboundlinks_nofollow)
    {
        $this->inboundlinks_nofollow = (int) $inboundlinks_nofollow;
    }

    public function getCanBeCrawled()
    {
        return $this->can_be_crawled;
    }

    public function setCanBeCrawled($can_be_crawled)
    {
        $this->can_be_crawled = (bool) $can_be_crawled;
    }

    public function getIndexable()
    {
        return $this->indexable;
    }

    public function setIndexable($indexable)
    {
        $this->indexable = (int) $indexable;
    }

    public function getMimeType()
    {
        return $this->mime_type;
    }

    public function getLinks()
    {
        return $this->links;
    }

    public function setLinks($links)
    {
        $this->links = (int) $links;
    }

    public function getLinksDuplicate()
    {
        return (int) $this->links_duplicate;
    }

    public function setLinksDuplicate($links_duplicate)
    {
        $this->links_duplicate = (int) $links_duplicate;
    }

    public function getLinksSelf()
    {
        return $this->links_self;
    }

    public function setLinksSelf($links_self)
    {
        $this->links_self = (int) $links_self;
    }

    public function getLinksInternal()
    {
        return $this->links_internal;
    }

    public function setLinksInternal($links_internal)
    {
        $this->links_internal = (int) $links_internal;
    }

    public function getLinksSub()
    {
        return $this->links_sub;
    }

    public function setLinksSub($links_sub)
    {
        $this->links_sub = (int) $links_sub;
    }

    public function getLinksExternal()
    {
        return $this->links_external;
    }

    public function setLinksExternal($links_external)
    {
        $this->links_external = (int) $links_external;
    }

    public function getWordCount()
    {
        return $this->word_count;
    }

    public function setWordCount($word_count)
    {
        $this->word_count = (int) $word_count;
    }

    public function getLoadTime()
    {
        return $this->load_time;
    }

    public function setLoadTime($load_time)
    {
        $this->load_time = (int) $load_time;
    }

    public function getSize()
    {
        return $this->size;
    }

    public function setSize($size)
    {
        $this->size = (int) $size;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getUpdatedAt()
    {
        return $this->updated_at;
    }

    public function setUpdatedAt($updated_at)
    {
        $this->updated_at = $updated_at;
    }
}
