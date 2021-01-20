<?php

namespace PiedWeb\SeoPocketCrawler;

class ExtractExternalLinks
{
    protected $id;
    protected $dir;
    protected $filter;
    protected $filterType;
    protected $external = [];

    /**
     * @var CrawlerConfig
     */
    protected $config;

    protected function __construct(string $id, ?string $dataDirectory = null)
    {
        $this->config = CrawlerConfig::loadFrom($id, $dataDirectory);
        $this->dir = $this->config->getDataFolder().'/links';
    }

    protected function filter($filename)
    {
        return 0 === strpos($filename, 'From_');
    }

    public static function scan(string $id)
    {
        $self = new self($id);
        $self->scanLinksDir();

        return $self->getExternals();
    }

    protected function scanLinksDir()
    {
        if ($resource = opendir($this->dir)) {
            while (false !== ($filename = readdir($resource))) {
                if ($this->filter($filename)) {
                    $this->harvestExternalLinks(
                        trim(file_get_contents($this->dir.'/'.$filename)),
                        $this->config->getUrlFromId(substr($filename, strlen('From_')))
                    );
                }
            }
            closedir($resource);
        }
    }

    protected function harvestExternalLinks(string $strUrlsLinked, $from)
    {
        if (empty($strUrlsLinked)) {
            return;
        }

        $lines = explode(chr(10), $strUrlsLinked);

        foreach ($lines as $line) {
            if (0 !== strpos($line, $this->config->getBase())) {
                if (! isset($this->external[$line])) {
                    $this->external[$line] = [];
                }
                $this->external[$line][] = $from;
            }
        }
    }

    public function getExternals()
    {
        return $this->external;
    }
}
