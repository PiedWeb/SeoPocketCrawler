<?php

namespace PiedWeb\SeoPocketCrawler\Command;

use PiedWeb\SeoPocketCrawler\LinksVisualizer;
use PiedWeb\SeoPocketCrawler\SimplePageRankCalculator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PageRankCommand extends Command
{
    protected static $defaultName = 'crawler:pagerank';

    protected $id;

    protected function configure()
    {
        $this->setDescription('Add internal page rank to index.csv');

        $this
            ->addArgument(
                'id',
                InputArgument::REQUIRED,
                'id from a previous crawl'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $pr = new SimplePageRankCalculator($input->getArgument('id'));

        echo $pr->record().PHP_EOL;

        new LinksVisualizer($input->getArgument('id'));

        return 0;
    }
}
