<?php

namespace PiedWeb\SeoPocketCrawler;

class ExtractExternalLinks
{
    protected $id;
    protected $dir;
    protected $base;
    protected $filter;
    protected $filterType;
    protected $external = [];

    protected function __construct(string $id, ?string $dataDirectoryBasePath = null)
    {
        $this->id = $id;
        $this->dir = rtrim($dataDirectoryBasePath ?? __DIR__.'/../data', '/').'/'.$id.'/links';

        if (!file_exists($this->dir.'/../config.json')) {
            throw new \Exception('no crawl results found for id `'.$id.'`');
        }
        $this->base = json_decode(file_get_contents($this->dir.'/../config.json'), true)['base'];
    }

    protected function filter($filename)
    {
        return 0 === strpos($filename, 'From_');
    }

    public static function scan(string $id)
    {
        $self = new self($id);
        $self->scanDir();

        return $self->getExternals();
    }

    protected function scanDir()
    {
        if ($resource = opendir($this->dir)) {
            while (false !== ($filename = readdir($resource))) {
                if ($this->filter($filename)) {
                    $this->harvestExternalLinks(file_get_contents($this->dir.'/'.$filename));
                }
            }
            closedir($resource);
        }
    }

    public function harvestExternalLinks(string $content)
    {
        $lines = explode(chr(10), $content);

        foreach ($lines as $line) {
            if (0 !== strpos($line, $this->base)) {
                $this->external[$line] = ($this->external[$line] ?? 0) + 1;
            }
        }
    }

    public function getExternals()
    {
        return $this->external;
    }
}
