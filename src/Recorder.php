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

    public function __construct($folder, $cacheMethod = Record::CACHE_ID)
    {
        $this->folder = $folder;
        $this->cacheMethod = $cacheMethod;

        //exec('rm -rf '.$folder);
        if (!file_exists($folder)) {
            mkdir($folder);
            mkdir($folder.Recorder::LINKS_DIR);
            mkdir($folder.Recorder::CACHE_DIR);
        }
    }

    public function getFolder()
    {
        return $this->folder;
    }

    public function cache(Harvest $harvest, Url $url)
    {
        if (Recorder::CACHE_NONE === $this->cacheMethod || !$this->mustWeCache($harvest)) {
            return;
        } elseif (Recorder::CACHE_URI === $this->cacheMethod) {
            return $this->cacheWithUrlAsFilename($harvest, $url);
        } else {
            return $this->cacheWithIdAsFilename($harvest, $url);
        }
    }

    protected function mustWeCache(Harvest $harvest)
    {
        return false !== strpos($harvest->getResponse()->getContentType(), 'text/html');
    }

    protected function cacheWithIdAsFilename(Harvest $harvest, Url $url)
    {
        return file_put_contents(
            $this->folder.Recorder::CACHE_DIR.'/'.$url->id,
            $harvest->getResponse()->getHeaders(false).PHP_EOL.PHP_EOL.$harvest->getResponse()->getContent()
        );
    }

    protected function cacheWithUrlAsFilename(Harvest $harvest, Url $url)
    {
        $url = trim($harvest->getAbsoluteInternalLink($harvest->getResponse()->getEffectiveUrl()), '/').'/';
        $urlPart = explode('/', $url);
        $folder = $this->folder.Recorder::CACHE_DIR;

        $urlPartLenght = count($urlPart);
        for ($i = 0; $i < $urlPartLenght; ++$i) {
            if ($i == $urlPartLenght - 1) {
                $filename = empty($urlPart[$i]) ? 'index.html' : $urlPart[$i];

                return file_put_contents(
                    $folder.'/'.$filename,
                    $harvest->getResponse()->getHeaders(false).PHP_EOL.PHP_EOL.$harvest->getResponse()->getContent()
                );
            } else {
                $folder .= '/'.$urlPart[$i];
                if (!file_exists($folder) || !is_dir($folder)) {
                    mkdir($folder);
                }
            }
        }
    }

    public function record(array $urls)
    {
        $fp = fopen($this->folder.'/index.csv', 'w');

        if (false !== $fp) {
            $header = array_keys(get_object_vars(array_values($urls)[0]));
            fputcsv($fp, $header);

            foreach ($urls as $url) {
                fputcsv($fp, get_object_vars($url));
            }

            fclose($fp);

            return true;
        }

        return false;
    }

    public function recordInboundLink(Url $from, Url $to)
    {
        file_put_contents($this->folder.Recorder::LINKS_DIR.'/To_'.$to->id, $from->uri, FILE_APPEND);
    }

    public function recordOutboundLink(Url $from, array $links)
    {
        $links = array_map(function ($link) {
            return $link->getUrl();
        }, $links);
        file_put_contents($this->folder.Recorder::LINKS_DIR.'/From_'.$from->id, implode(PHP_EOL, $links));
    }
}
