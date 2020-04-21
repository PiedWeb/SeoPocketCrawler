<?php

namespace PiedWeb\SeoPocketCrawler;

use League\Csv\Reader;
use PiedWeb\UrlHarvester\Harvest;
use Spatie\Robots\RobotsTxt;

class CrawlerConfig
{
    /**
     * @var string contain the user agent used during the crawl
     */
    protected $userAgent;

    /**
     * @var string crawl id
     */
    protected $id;

    /**
     * @var string page to ignore during the crawl
     */
    protected $ignore;

    /**
     * @var int depth max where to crawl
     */
    protected $limit;

    /**
     * @var string contain https://domain.tdl from start url
     */
    protected $base;

    /**
     * @var int
     */
    protected $wait;

    /**
     * @var int
     */
    protected $cacheMethod;

    /**
     * @var string
     */
    protected $dataDirectory;

    /**
     * @var string
     */
    protected $startUrl;

    protected $request;
    protected $robotsTxt;

    /** @var Recorder */
    protected $recorder;

    public function __construct(
        string $startUrl,
        string $ignore,
        int $limit,
        string $userAgent,
        int $cacheMethod = Recorder::CACHE_ID,
        int $waitInMicroSeconds = 100000,
        ?string $dataDirectory = null
    ) {
        $this->setBaseAndStartUrl($startUrl);
        //$this->urls[$this->startUrl] = null;
        $this->id = date('ymdHi').'-'.parse_url($this->base, PHP_URL_HOST);
        $this->ignore = $ignore;
        $this->userAgent = $userAgent;
        $this->limit = $limit;
        $this->cacheMethod = $cacheMethod;
        $this->wait = $waitInMicroSeconds;
        $this->dataDirectory = rtrim($dataDirectory ?? __DIR__.'/../data', '/');
    }

    /**
     * @return string id
     */
    public static function getLastCrawl(string $dataDirectory): string
    {
        $dir = scandir($dataDirectory);
        $lastCrawl = null;
        $lastRunAt = null;

        foreach ($dir as $file) {
            if ('.' != $file && '..' != $file
                && is_dir($dataDirectory.'/'.$file)
                && filemtime($dataDirectory.'/'.$file) > $lastRunAt) {
                $lastCrawl = $file;
                $lastRunAt = filemtime($dataDirectory.'/'.$file);
            }
        }

        if (null === $lastCrawl) {
            throw new \Exception('No crawl previously runned');
        }

        return $lastCrawl;
    }

    public static function loadFrom(string $crawlId, ?string $dataDirectory = null): self
    {
        if ('last' === $crawlId) {
            $crawlId = self::getLastCrawl(rtrim(self::getDataFolderFrom('', $dataDirectory), '/'));
        }

        $configFilePath = self::getDataFolderFrom($crawlId, $dataDirectory).'/config.json';
        if (!file_exists($configFilePath)) {
            throw new \Exception('Crawl `'.$crawlId.'` not found.');
        }
        $config = json_decode(file_get_contents($configFilePath), true);

        return (new self(
            $config['base'].$config['startUrl'],
            $config['ignore'],
            intval($config['limit']),
            (string) $config['userAgent'],
            intval($config['cacheMethod']),
            intval($config['wait']),
            $dataDirectory
        ))->setId($crawlId);
    }

    public function recordConfig()
    {
        $this->getRecorder(); // permit to create folder
        file_put_contents($this->getDataFolder().'/config.json', json_encode([
            'startUrl' => $this->startUrl,
            'base' => $this->base,
            'ignore' => $this->ignore,
            'limit' => $this->limit,
            'userAgent' => $this->userAgent,
            'cacheMethod' => $this->cacheMethod,
            'wait' => $this->wait,
        ]));
    }

    protected function setBaseAndStartUrl(string $url)
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new \Exception('start is not a valid URL `'.$url.'`');
        }

        $this->base = preg_match('@^(http://|https://)?[^/\?#]+@', $url, $match) ? $match[0] : $url;

        $url = substr($url, strlen($this->base));

        $this->startUrl = (!isset($url[0]) || '/' != $url[0] ? '/' : '').$url;
    }

    public static function getDataFolderFrom(string $id, ?string $path)
    {
        return ($path ?? __DIR__.'/../data').'/'.$id;
    }

    public function getDataFolder()
    {
        return $this->dataDirectory.'/'.$this->id;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getBase()
    {
        return $this->base;
    }

    public function getStartUrl()
    {
        return $this->startUrl;
    }

    public function getWait()
    {
        return $this->wait;
    }

    public function getUserAgent()
    {
        return $this->userAgent;
    }

    public function getLimit()
    {
        return $this->limit;
    }

    public function getCacheMethod()
    {
        return $this->cacheMethod;
    }

    public function getDataDirectory()
    {
        $this->dataDirectory;
    }

    /** @var RobotsTxt */
    protected $virtualRobots;

    public function getVirtualRobots()
    {
        if (null === $this->virtualRobots) {
            $this->virtualRobots = new RobotsTxt($this->ignore);
        }

        return $this->virtualRobots;
    }

    public function setId(string $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getDataFromPreviousCrawl()
    {
        $dataFilePath = $this->getDataFolder().'/data.csv';
        if (!file_exists($dataFilePath)) {
            throw new \Exception('Previous crawl\'s data not found (index.csv)');
        }

        $urls = [];
        $counter = 0;

        $csv = Reader::createFromPath($dataFilePath, 'r');
        $csv->setHeaderOffset(0);

        $records = $csv->getRecords();
        foreach ($records as $r) {
            $urls[$r['uri']] = new Url($this->base.$r['uri'], 0);
            foreach ($r as $k => $v) {
                if ('can_be_crawled' == $k && !empty($v)) {
                    $v = (bool) $v;
                }
                $urls[$r['uri']]->$k = $v;
            }
            if (!empty($r['can_be_crawled'])) {
                ++$counter;
            }
        }

        $currentClick = $r['click'] ?? 0;

        return [
            'urls' => $urls,
            'counter' => $counter,
            'currentClick' => $currentClick,
        ];
    }

    // could be add in an other class..
    protected $index;

    protected function getIndexFromPreviousCrawl()
    {
        if (null !== $this->index) {
            return $this->index;
        }

        $this->index = [];

        $indexFilePath = $this->getDataFolder().'/index.csv';
        if (!file_exists($indexFilePath)) {
            throw new \Exception('Previous crawl\'s data not found (index.csv)');
        }

        $csv = Reader::createFromPath($indexFilePath, 'r');
        $csv->setHeaderOffset(0);

        $records = $csv->getRecords();
        foreach ($records as $r) {
            $this->index[$r['id']] = new Url($this->base.$r['uri'], 0);
            $this->index[$r['id']]->id = $r['id'];
        }

        return $this->index;
    }

    public function getUrlFromId($id, $base = true)
    {
        $index = $this->getIndexFromPreviousCrawl();

        return isset($index[$id]) ? ($base ? $this->base : '').$index[$id]->uri : null;
    }

    public function cacheRequest($harvest)
    {
        if ($harvest instanceof Harvest && null !== $harvest->getResponse()->getRequest()) {
            $this->request = $harvest->getResponse()->getRequest();
        }

        return $this;
    }

    public function getRequestCached()
    {
        return $this->request;
    }

    public function cacheRobotsTxt($harvest)
    {
        if (null === $this->robotsTxt && $harvest instanceof Harvest) {
            $this->robotsTxt = $harvest->getRobotsTxt();
        }

        return $this;
    }

    public function getRobotsTxtCached()
    {
        return $this->robotsTxt;
    }

    public function getRecorder()
    {
        return $this->recorder ?? $this->recorder = new Recorder($this->getDataFolder(), $this->getCacheMethod());
    }
}
