<?php

namespace PiedWeb\SeoPocketCrawler;

use PiedWeb\UrlHarvester\Harvest;

class Url
{
    private static $autoIncrement = 1;

    public $id;
    public $discovered;
    public $uri;
    public $updated_at;
    public $click;
    public $inboundlinks;
    public $can_be_crawled;
    public $indexable;
    public $mime_type;
    public $links;
    public $links_duplicate;
    public $links_self;
    public $links_internal;
    public $links_sub;
    public $links_external;
    public $ratio_text_code;
    public $load_time;
    public $size;
    public $title;
    public $kws;
    public $h1;
    public $breadcrumb_level;
    public $breadcrumb_first;
    public $breadcrumb_text;

    public function __construct($url, $click)
    {
        $this->id = $this->getId();
        $this->uri = substr($url, strlen(Harvest::getDomainAndSchemeFrom($url)));
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
}
