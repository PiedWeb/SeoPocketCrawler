<?php

namespace PiedWeb\SeoPocketCrawler\Command;

use PiedWeb\SeoPocketCrawler\ExtractExternalLinks;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ShowExternalLinksCommand extends Command
{
    protected static $defaultName = 'crawler:external-links';

    protected $id;

    protected function configure()
    {
        $this->setDescription('List external domain linked.');

        $this
            ->addArgument(
                'id',
                InputArgument::REQUIRED,
                'id from a previous crawl'
            )
            ->addOption('host', 'ho', InputOption::VALUE_NONE, 'get only host')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $table = new Table($output);

        $table->setHeaders($input->getOption('host') ? ['Host'] : ['url', 'from']);

        $links = ExtractExternalLinks::scan((string) $input->getArgument('id'));
        arsort($links);
        $ever = [];
        foreach ($links as $link => $from) {
            if ($input->getOption('host')) {
                $host = parse_url($link, PHP_URL_HOST);
                if ($host && !isset($ever[$host])) {
                    $ever[$host] = 1;
                    $table->addRow([$host]);
                }
            } else {
                $first = true;
                foreach ($from as $url) {
                    $table->addRow([true === $first ? $link : '', $url]);
                    $first = false;
                }
            }
        }

        $table->render();

        return 0;
    }
}
