<?php

namespace PiedWeb\SeoPocketCrawler;

use League\Csv\Reader;

/**
 * Page Rank Calculator.
 */
class SimplePageRankCalculator
{
    /**
     * @var CrawlerConfig
     */
    protected $config;

    /**
     * @var int
     */
    protected $pagesNbr;

    /**
     * @var array [id, pagerank]
     */
    protected $results;

    protected $maxIteration = 10000;

    /**
     * @var array
     */
    protected $linksTo = [];
    protected $nbrLinksFrom = [];

    protected $dampingFactor = 0.85;

    public function __construct(string $id, ?string $dataDirectory = null)
    {
        $this->config = CrawlerConfig::loadFrom($id, $dataDirectory);

        $this->initLinksIndex();

        $this->results = array_fill_keys(array_reverse(array_keys($this->linksTo)), null);

        for ($iteration = 0; $iteration < $this->maxIteration; ++$iteration) {
            $this->calcul();
        }
    }

    public function record()
    {
        // merge it with previous data harvested
        $data = $this->config->getDataFromPreviousCrawl();
        $urls = $data['urls'];

        foreach ($urls as $k => $url) {
            if (isset($this->results[$url->id])) {
                $urls[$k]->pagerank = $this->results[$url->id];
            }
        }

        (new Recorder($this->config->getDataFolder(), $this->config->getCacheMethod()))->record($urls);

        // return data filepath
        return realpath($this->config->getDataFolder()).'/data.csv';
    }

    protected function calcul()
    {
        $ids = array_keys($this->results);
        foreach ($ids as $id) {
            $sumPR = 0;
            foreach ($this->getLinksTo($id) as $link) {
                $sumPR = $sumPR + $this->results[$link] / $this->getNbrLinksFrom($link);
            }

            $this->results[$id] = $this->dampingFactor * $sumPR + (1 - $this->dampingFactor) / $this->getPagesNbr();
        }
    }

    protected function getPagesNbr()
    {
        if (null !== $this->pagesNbr) {
            return $this->pagesNbr;
        }

        return $this->pagesNbr = count($this->linksTo);
    }

    protected function getLinksTo(int $id): ?array
    {
        return $this->linksTo[$id];
    }

    protected function getNbrLinksFrom(int $id): ?int
    {
        return $this->nbrLinksFrom[$id];
    }

    protected function initLinksIndex()
    {
        $csv = Reader::createFromPath($this->config->getDataFolder().Recorder::LINKS_DIR.'/Index.csv', 'r');
        $csv->setHeaderOffset(0);

        $records = $csv->getRecords();
        foreach ($records as $r) {
            if (! isset($this->linksTo[$r['To']])) {
                $this->linksTo[$r['To']] = [];
            }
            $this->linksTo[$r['To']][] = $r['From'];

            $this->nbrLinksFrom[$r['From']] = ($this->nbrLinksFrom[$r['From']] ?? 0) + 1;
        }
    }
}
