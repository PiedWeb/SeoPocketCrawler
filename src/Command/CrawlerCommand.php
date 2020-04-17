<?php

namespace PiedWeb\SeoPocketCrawler\Command;

use PiedWeb\SeoPocketCrawler\Crawler;
use PiedWeb\SeoPocketCrawler\CrawlerContinue;
use PiedWeb\SeoPocketCrawler\CrawlerRestart;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CrawlerCommand extends Command
{
    protected static $defaultName = 'crawler:go';

    protected $id;

    protected function configure()
    {
        $this->setDescription('Crawl a website.');

        $this
            ->addArgument(
                'start',
                InputArgument::REQUIRED,
                'Define where the crawl start. Eg: https://piedweb.com'
                .PHP_EOL.'You can specify an id from a previous crawl. Other options will not be listen.'
            )
            ->addOption('limit', 'l', InputOption::VALUE_REQUIRED, 'Define where a depth limit', 5)
            ->addOption(
                'ignore',
                'i',
                InputOption::VALUE_REQUIRED,
                'Virtual Robots.txt to respect (could be a string or an URL).'
            )
            ->addOption(
                'user-agent',
                'u',
                InputOption::VALUE_REQUIRED,
                'Define the user-agent used during the crawl.',
                'SEO Pocket Crawler - PiedWeb.com/seo/crawler'
            )
            ->addOption(
                'wait',
                'w',
                InputOption::VALUE_REQUIRED,
                'In Microseconds, the time to wait between 2 requests. Default 0,1s.',
                100000
            )
            ->addOption(
                'cache-method',
                'c',
                InputOption::VALUE_REQUIRED,
                'In Microseconds, the time to wait between two request. Default : 100000 (0,1s).',
                \PiedWeb\SeoPocketCrawler\Recorder::CACHE_ID
            )
            ->addOption(
                'restart',
                'r',
                InputOption::VALUE_REQUIRED,
                'Permit to restart a previous crawl. Values 1 = fresh restart, 2 = restart from cache'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->checkArguments($input);

        $start = microtime(true);

        $crawler = $this->initCrawler($input);

        $output->writeln(['', '', 'Crawl starting !', '============', '', 'ID: '.$crawler->getConfig()->getId()]);
        $output->writeln([
            null !== $this->id ? ($input->getOption('restart') ? 'Restart' : 'Continue') : '',
            '',
            'Details : ',
            '- Crawl starting at '.$crawler->getConfig()->getBase().$crawler->getConfig()->getStartUrl(),
            '- User-Agent used `'.$crawler->getConfig()->getUserAgent(),
            '- `'.$crawler->getConfig()->getWait().' ms between two requests',
        ]);

        $crawler->crawl(!$input->getOption('quiet'));

        $end = microtime(true);

        $output->writeln(['', '---------------', 'Crawl succeed', 'You can find your data in ']);

        echo realpath($crawler->getConfig()->getDataFolder()).'/data.csv'.PHP_EOL;

        $output->writeln(['', '', '----Chrono----', (round(($end - $start), 2)).'s', '', '']);

        return 0;
    }

    public function checkArguments(InputInterface $input)
    {
        if (!filter_var($input->getArgument('start'), FILTER_VALIDATE_URL)) {
            $this->id = $input->getArgument('start');
        }
    }

    /**
     * @return Crawler
     */
    public function initCrawler(InputInterface $input)
    {
        if (null === $this->id) {
            return new Crawler(
                (string) $input->getArgument('start'),
                $this->loadVirtualRobotsTxt($input),
                intval($input->getOption('limit')),
                (string) $input->getOption('user-agent'),
                intval($input->getOption('cache-method')),
                intval($input->getOption('wait'))
            );
        }

        if ($input->getOption('restart')) {
            return new CrawlerRestart(
                $this->id,
                2 == $input->getOption('restart') ? true : false // $fromCache
            );
        }

        return new CrawlerContinue($this->id);
    }

    public function loadVirtualRobotsTxt(InputInterface $input)
    {
        if (null === $input->getOption('ignore')) {
            return '';
        }

        $ignore = (string) $input->getOption('ignore');

        if (filter_var($ignore, FILTER_VALIDATE_URL)) {
            return \PiedWeb\Curl\Request::get($ignore);
        }

        if (file_exists($ignore)) {
            return file_get_contents($ignore);
        }

        throw new \Exception('An error occured with your --ignore option');
    }
}
