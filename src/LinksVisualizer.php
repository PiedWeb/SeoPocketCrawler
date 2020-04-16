<?php

namespace PiedWeb\SeoPocketCrawler;

use League\Csv\Reader;

/**
 * Not used anymore...
 */
class LinksVisualizer
{
    /**
     * @var CrawlerConfig
     */
    protected $config;

    protected $results = ['nodes' => [], 'links' => []];

    public function __construct(string $id, ?string $dataDirectory = null)
    {
        $this->config = CrawlerConfig::loadFrom($id, $dataDirectory);

        //$this->loadNodes();
        //$this->loadLinks();

        file_put_contents(
            $this->config->getDataFolder().'/pagerank.html',
            file_get_contents(dirname(__FILE__).'/Resources/PageRankVisualizer.html')
        );
        /*
        file_put_contents(
            $this->config->getDataFolder().Recorder::LINKS_DIR.'/data.json',
            json_encode($this->results, JSON_PRETTY_PRINT)
        );**/
    }

    protected function loadLinks()
    {
        $csv = Reader::createFromPath($this->config->getDataFolder().Recorder::LINKS_DIR.'/Index.csv', 'r');
        $csv->setHeaderOffset(0);
        $records = $csv->getRecords();
        foreach ($records as $r) {
            if (
                $r['To'] > 0 // pas de liens externe
                && isset($this->results['nodes'][$r['From']]) && isset($this->results['nodes'][$r['To']])
            ) {
                $this->results['links'][] = ['target' => $r['From'], 'source' => $r['To']];
            }
        }

        $this->results['nodes'] = array_values($this->results['nodes']);
    }

    protected function loadNodes()
    {
        $urls = $this->config->getDataFromPreviousCrawl()['urls'];

        foreach ($urls as $url) {
            if (1 == $url->mime_type) { //seulement html
                $this->results['nodes'][$url->id] = ['id' => $url->id, 'pagerank' => $url->pagerank, 'uri' => $url->uri];
            }
        }
    }
}
