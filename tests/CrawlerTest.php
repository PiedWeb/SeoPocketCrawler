<?php

declare(strict_types=1);

namespace PiedWeb\SeoPocketCrawler\Test;

use PiedWeb\SeoPocketCrawler\Crawler;
use PiedWeb\SeoPocketCrawler\CrawlerContinue;
use PiedWeb\SeoPocketCrawler\CrawlerRestart;
use PiedWeb\SeoPocketCrawler\Recorder;
use PiedWeb\SeoPocketCrawler\SimplePageRankCalculator;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class CrawlerTest extends \PHPUnit\Framework\TestCase
{
    public function testIt()
    {
        $crawl = new Crawler(
            'https://dev.piedweb.com/',
            '',
            0,
            'SeoPocketCrawler Test',
            Recorder::CACHE_ID,
            0,
            false
        );
        $crawl->crawl(true);

        $this->assertTrue(file_exists($crawl->getConfig()->getDataFolder().'/index.csv'));

        $id = $crawl->getConfig()->getId();

        $crawlerRestart = new CrawlerRestart($id, true, false);
        $crawlerRestart->crawl(true);
        // todo test
        $crawlerRestart = new CrawlerContinue($id, false);
        $crawlerRestart->crawl(true);
        // todo test
        $prCalculator = new SimplePageRankCalculator($id);
        $prCalculator->record();
        // todo test
    }

    public function testCommand()
    {
        $application = new Application();

        $application->add(new \PiedWeb\SeoPocketCrawler\Command\CrawlerCommand());
        $application->add(new \PiedWeb\SeoPocketCrawler\Command\ShowExternalLinksCommand());
        $application->add(new \PiedWeb\SeoPocketCrawler\Command\PageRankCommand());

        $command = $application->find('crawler:go');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'start' => 'https://dev.piedweb.com',
            '--quiet',
            // prefix the key with two dashes when passing options,
            // e.g: '--some-option' => 'option_value',
        ]);

        // the output of the command in the console
        $output = $commandTester->getDisplay();
        $this->assertTrue(strpos($output, 'piedweb.com') !== false);
    }

    public function testWithCacheUriAsFilename()
    {
        $crawl = new Crawler('https://dev.piedweb.com/', '', 0, 'PHPUnit', Recorder::CACHE_URI, 0, false);
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
