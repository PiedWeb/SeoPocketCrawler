<p align="center"><a href="https://dev.piedweb.com">
<img src="https://raw.githubusercontent.com/PiedWeb/piedweb-devoluix-theme/master/src/img/logo_title.png" width="200" height="200" alt="Open Source Package" />
</a></p>

# CLI Seo Pocket Crawler

[![Latest Version](https://img.shields.io/github/tag/PiedWeb/SeoPocketCrawler.svg?style=flat&label=release)](https://github.com/PiedWeb/SeoPocketCrawler/tags)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat)](https://github.com/PiedWeb/SeoPocketCrawler/blob/master/LICENSE)
[![Build Status](https://img.shields.io/travis/PiedWeb/SeoPocketCrawler/master.svg?style=flat)](https://travis-ci.org/PiedWeb/SeoPocketCrawler)
[![Quality Score](https://img.shields.io/scrutinizer/g/PiedWeb/SeoPocketCrawler.svg?style=flat)](https://scrutinizer-ci.com/g/PiedWeb/SeoPocketCrawler)
[![Code Coverage](https://img.shields.io/scrutinizer/coverage/g/PiedWeb/SeoPocketCrawler.svg?style=flat)](https://scrutinizer-ci.com/g/PiedWeb/SeoPocketCrawler/code-structure)
[![Total Downloads](https://img.shields.io/packagist/dt/piedweb/seo-pocket-crawler.svg?style=flat)](https://packagist.org/packages/piedweb/seo-pocket-crawler)

Web Crawler to check few SEO basics.

Use the collected data in your favorite spreadsheet software.

French documentation available :
https://piedweb.com/seo/crawler

## Install

Via [Packagist](https://img.shields.io/packagist/dt/piedweb/seo-pocket-crawler.svg?style=flat)

``` bash
$ composer create-project piedweb/seo-pocket-crawler
```

## Usage

``` bash
$ bin/crawler --start="https://piedweb.com"
```

Other args:
```
    --start -s
        Define where the crawl start.

    --limit -l
        Define where a depth limit for the crawler (default 5).

    --ignore -i
        Virtual Robots.txt wich will be interpreted for this crawl (could be a
        string or an URL).

    --user-agent -u
        Define the user-agent used during the crawl

    --verbose -v
        Display debugging information (0/1, default 1).

    --wait -w
        In Microseconds, the time to wait between two request. Default : 100000
        (0,1s).

    --cache-method -c
        Keep a copy for each html crawled page : 0 (no),2 (with filename
        corresponding to the ID),1 (with filename corresponding to the Uri).

```

## Testing

``` bash
$ composer test
```

## Contributing

Please see [contributing](https://dev.piedweb.com/contributing)

## Credits

- [PiedWeb](https://piedweb.com) ak [Robind4](https://twitter.com/Robind4)
- [All Contributors](https://github.com/PiedWeb/:package_skake/graphs/contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.

[![Latest Version](https://img.shields.io/github/tag/PiedWeb/SeoPocketCrawler.svg?style=flat&label=release)](https://github.com/PiedWeb/SeoPocketCrawler/tags)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat)](https://github.com/PiedWeb/SeoPocketCrawler/blob/master/LICENSE)
[![Build Status](https://img.shields.io/travis/PiedWeb/SeoPocketCrawler/master.svg?style=flat)](https://travis-ci.org/PiedWeb/SeoPocketCrawler)
[![Quality Score](https://img.shields.io/scrutinizer/g/PiedWeb/SeoPocketCrawler.svg?style=flat)](https://scrutinizer-ci.com/g/PiedWeb/SeoPocketCrawler)
[![Code Coverage](https://img.shields.io/scrutinizer/coverage/g/PiedWeb/SeoPocketCrawler.svg?style=flat)](https://scrutinizer-ci.com/g/PiedWeb/SeoPocketCrawler/code-structure)
[![Total Downloads](https://img.shields.io/packagist/dt/piedweb/seo-pocket-crawler.svg?style=flat)](https://packagist.org/packages/piedweb/seo-pocket-crawler)
