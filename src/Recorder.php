<?php

namespace PiedWeb\SeoPocketCrawler;

use PiedWeb\UrlHarvester\Harvest;

class Recorder
{
    const LINKS_DIR = '/links';
    const CACHE_DIR = '/cache';

    const CACHE_NONE = 0;
    const CACHE_ID = 2;
    const CACHE_URI = 1;

    protected $folder;
    protected $cacheMethod;

    public function __construct($folder, $cacheMethod = self::CACHE_ID)
    {
        $this->folder = $folder;
        $this->cacheMethod = $cacheMethod;

        if (!file_exists($folder)) {
            mkdir($folder);
            mkdir($folder.Recorder::LINKS_DIR);
            mkdir($folder.Recorder::CACHE_DIR);
            $this->initLinksIndex();
        }
    }

    public function cache(Harvest $harvest, Url $url)
    {
        if (Recorder::CACHE_NONE === $this->cacheMethod || !$this->mustWeCache($harvest)) {
            return;
        }

        $filePath = $this->getCacheFilePath($url);
        if (!file_exists($filePath)) {
            file_put_contents(
                $filePath,
                $harvest->getResponse()->getHeaders(false).PHP_EOL.PHP_EOL.$harvest->getResponse()->getContent()
            );

            return file_put_contents($filePath.'---info', json_encode($harvest->getResponse()->getInfo()));
        }
    }

    public function getCacheFilePath(Url $url)
    {
        if (Recorder::CACHE_URI === $this->cacheMethod) {
            return $this->getCacheFilePathWithUrlAsFilename($url);
        } else {
            return $this->getCacheFilePathWithIdAsFilename($url);
        }
    }

    protected function getCacheFilePathWithUrlAsFilename(Url $url)
    {
        $url = trim($url->uri, '/').'/';
        $urlPart = explode('/', $url);
        $folder = $this->folder.Recorder::CACHE_DIR;

        $urlPartLenght = count($urlPart);
        for ($i = 0; $i < $urlPartLenght; ++$i) {
            if ($i == $urlPartLenght - 1) {
                return $folder.'/'.(empty($urlPart[$i]) ? 'index.html' : $urlPart[$i]);
            } else {
                $folder .= '/'.$urlPart[$i];
                if (!file_exists($folder) || !is_dir($folder)) {
                    mkdir($folder);
                }
            }
        }
    }

    protected function getCacheFilePathWithIdAsFilename(Url $url)
    {
        return $this->folder.Recorder::CACHE_DIR.'/'.(string) $url->id;
    }

    protected function mustWeCache(Harvest $harvest)
    {
        return false !== strpos($harvest->getResponse()->getContentType(), 'text/html');
    }

    public function record(array $urls)
    {
        $dataCsv = fopen($this->folder.'/data.csv', 'w');
        $indexCsv = fopen($this->folder.'/index.csv', 'w');

        if (false !== $dataCsv) {
            $header = array_keys(get_object_vars(array_values($urls)[0]));
            fputcsv($dataCsv, $header);
            fputcsv($indexCsv, ['id', 'uri']);

            foreach ($urls as $url) {
                fputcsv($dataCsv, get_object_vars($url));
                fputcsv($indexCsv, [$url->id, $url->uri]);
            }

            fclose($dataCsv);

            return true;
        }

        return false;
    }

    public function recordInboundLink(Url $from, Url $to, int $type)
    {
        file_put_contents(
            $this->folder.Recorder::LINKS_DIR.'/To_'.(string) $to->id.'_'.$type,
            $from->uri.PHP_EOL,
            FILE_APPEND
        );
    }

    public function recordOutboundLink(Url $from, array $links)
    {
        $links = array_map(function ($link) {
            return $link->getUrl();
        }, $links);
        file_put_contents($this->folder.Recorder::LINKS_DIR.'/From_'.(string) $from->id, implode(PHP_EOL, $links));
    }

    protected function initLinksIndex()
    {
        if (!file_exists($this->folder.Recorder::LINKS_DIR.'/Index.csv')) {
            file_put_contents($this->folder.Recorder::LINKS_DIR.'/Index.csv', 'From,To'.PHP_EOL);
        }
    }

    public static function removeBase(string $base, string $url)
    {
        return (0 === strpos($url, $base)) ? $newstring = substr_replace($url, '', 0, strlen($base)) : null;
    }

    public function recordLinksIndex(string $base, Url $from, $urls, array $links)
    {
        $everAdded = [];
        $content = '';

        foreach ($links as $link) {
            $content .= $from->getId();
            if (in_array($link->getUrl(), $everAdded)) { // like Google, we sould not add duplicate link,
                // so we say the juice is lost -1
                $content .= ',-1'.PHP_EOL;
            } else {
                $everAdded[] = $link->getUrl();
                $relative = self::removeBase($base, $link->getPageUrl());
                $content .= ','.(isset($urls[$relative]) ? $urls[$relative]->getId() : 0).PHP_EOL; // 0 = external
            }
        }
        file_put_contents($this->folder.Recorder::LINKS_DIR.'/Index.csv', $content, FILE_APPEND);
    }
}
