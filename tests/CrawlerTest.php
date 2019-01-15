<?php

declare(strict_types=1);

namespace PiedWeb\SeoPocketCrawler\Test;

use PiedWeb\SeoPocketCrawler\Crawler;
use PiedWeb\SeoPocketCrawler\Recorder;

class CrawlerTest extends \PHPUnit\Framework\TestCase
{
    public function testIt()
    {
        $crawl = new Crawler('https://piedweb.com/', '', 0, 'PHPUnit');
        $crawl->crawl(true);

        $this->assertTrue(file_exists($crawl->getDataFolder().'/index.csv'));
    }

    public function testWithCacheUriAsFilename()
    {
        $crawl = new Crawler('https://piedweb.com/', '', 0, 'PHPUnit', Recorder::CACHE_URI);
        $crawl->crawl(false);

        $this->assertTrue(file_exists($crawl->getDataFolder().'/index.csv'));
    }
}
