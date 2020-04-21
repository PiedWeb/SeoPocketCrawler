<?php

declare(strict_types=1);

namespace PiedWeb\SeoPocketCrawler\Test;

use PiedWeb\SeoPocketCrawler\Crawler;
use PiedWeb\SeoPocketCrawler\CrawlerRestart;
use PiedWeb\SeoPocketCrawler\CrawlerContinue;
use PiedWeb\SeoPocketCrawler\Recorder;
use PiedWeb\SeoPocketCrawler\SimplePageRankCalculator;

class CrawlerTest extends \PHPUnit\Framework\TestCase
{
    public function testIt()
    {
        $crawl = new Crawler('https://piedweb.com/', '', 0, 'PHPUnit');
        $crawl->crawl(true);

        $this->assertTrue(file_exists($crawl->getConfig()->getDataFolder().'/index.csv'));

        $id = $crawl->getConfig()->getId();

        $crawlerRestart = new CrawlerRestart($id);
        $crawlerRestart->crawl(true);
        // todo test
        $crawlerRestart = new CrawlerContinue($id);
        $crawlerRestart->crawl(true);
        // todo test
        $prCalculator = new SimplePageRankCalculator($id);
        $prCalculator->record();
        // todo test
    }

    public function testWithCacheUriAsFilename()
    {
        $crawl = new Crawler('https://piedweb.com/', '', 0, 'PHPUnit', Recorder::CACHE_URI);
        $crawl->crawl(false);

        $this->assertTrue(file_exists($crawl->getConfig()->getDataFolder().'/index.csv'));

        $restart = new CrawlerRestart($crawl->getConfig()->getId());
        $restart->crawl(true);

        $continue = new CrawlerContinue($crawl->getConfig()->getId());
        $continue->crawl(true);

        $restart = new CrawlerRestart($crawl->getConfig()->getId(), true);
        $restart->crawl(false);

        $this->assertTrue(file_exists($crawl->getConfig()->getDataFolder().'/index.csv'));
    }
}
