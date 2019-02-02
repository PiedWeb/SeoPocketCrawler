<?php

declare(strict_types=1);

namespace PiedWeb\SeoPocketCrawler\Test;

use PiedWeb\SeoPocketCrawler\Crawler;
use PiedWeb\SeoPocketCrawler\ExtractExternalLinks;

class ExternalTest extends \PHPUnit\Framework\TestCase
{
    public function testIt()
    {
        $crawl = new Crawler('https://piedweb.com/', '', 0, 'PHPUnit');
        $crawl->crawl(true);

        $this->assertTrue(file_exists($crawl->getDataFolder().'/index.csv'));

        $id = $crawl->getId();
        $links = ExtractExternalLinks::scan($id);

        $this->assertTrue(is_array($links));
    }
}
