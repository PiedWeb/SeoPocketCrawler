<?php

namespace PiedWeb\SeoPocketCrawler;

use PiedWeb\UrlHarvester\Harvest;

/* http://www.convertcsv.com/csv-to-sql.htm
CREATE TABLE mytable(
   id               INTEGER  NOT NULL PRIMARY KEY
  ,discovered       INTEGER  NOT NULL
  ,uri              VARCHAR(400) NOT NULL
  ,updated_at       DATE
  ,click            INTEGER  NOT NULL
  ,inboundlinks     INTEGER  NOT NULL
  ,inboundlinks_nofollow INTEGER  NOT NULL
  ,can_be_crawled   BIT  NOT NULL
  ,indexable        BIT  NOT NULL
  ,mime_type        VARCHAR(10) NOT NULL
  ,links            INTEGER  NOT NULL
  ,links_duplicate  INTEGER  NOT NULL
  ,links_self       BIT  NOT NULL
  ,links_internal   INTEGER  NOT NULL
  ,links_sub        BIT  NOT NULL
  ,links_external   INTEGER  NOT NULL
  ,words_count      INTEGER  NOT NULL
  ,load_time        NUMERIC(8,6) NOT NULL
  ,size             INTEGER  NOT NULL
  ,title            VARCHAR(90)
  ,kws              VARCHAR(94) NOT NULL
  ,h1               VARCHAR(84) NOT NULL
  ,breadcrumb_level INTEGER
  ,breadcrumb_first VARCHAR(16)
  ,breadcrumb_text  VARCHAR(41)
);
*/
class Url
{
    private static $autoIncrement = 1;

    public $id;
    public $discovered;
    public $uri;
    public $updated_at;
    public $click;
    public $inboundlinks;
    public $inboundlinks_nofollow;
    public $can_be_crawled;
    public $indexable;
    public $mime_type;
    public $links;
    public $links_duplicate;
    public $links_self;
    public $links_internal;
    public $links_sub;
    public $links_external;
    public $words_count;
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
