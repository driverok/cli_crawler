<?php
/**
 * Web page crawler
 *
 * @author driverok <driverok@gmail.com>
 */

namespace driverok;

class Crawler
{
    /** @var string $logFileName where log file located */
    public $logFilename = '/tmp/crawler.log';

    /** @var int $timeout timeout to sleep when got an error */
    public $timeout = 10;

    /** @var int $pageLimit limit of loaded pages */
    public $pageLimit = 100;

    /** @var int $resultsLimit limit of founded description */
    public $resultsLimit = 10;

    /** @var int $countPages count loaded pages */
    private $countPages = 0;

    /** @var int $countResults count of founded descriptions */
    private $countResults = 0;

    /** @var string $domain domain to proceed */
    private $domain;

    /** @var string $protocol protocol to proceed */
    private $protocol = 'http://';

    /** @var Database $database object of db class */
    private $database;

    /** @var string $userAgent userAgent to hello webserver */
    private $userAgent = 'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 5.1; Trident/4.0)';

    /** @var array $queue queue of urls to proceed */
    private $queue = [];

    /** @var array $page array of currently checked page information */
    private $page = array();


    /**
     * Read the --domain cli param and set private var $domain
     */
    public function __construct()
    {
        $params = getopt("d:p:s::");
        $this->checkParams($params);
        $this->setDomain($params['d']);
        $this->setProtocol($params['p']);
        $this->addQueue($this->protocol.$this->domain);
        $this->database = new Database();
        if (!empty($params['s'])) {
            $this->setup();
        }
    }

    /**
     * Recursive function for checking urls from queue
     */
    public function run()
    {
        if (empty($this->queue)) {
            $this->log('Nothing to parse');
            return;
        }
        foreach ($this->queue as $url) {
            if ($this->needToStop())
            {
                $this->writeln('Time to stop our crawler');
                return;
            }
            $this->getUrl($url);
            $this->analyzeContent($this->page['content']);
            
            $this->writeln(mb_substr($this->page['description'], 0, 50,'utf-8').'... '.$url
            );
            $this->saveUrl();
            unset($this->queue[md5(trim($url, '/'))]);

        }

        if (!empty($this->queue)) {
            $this->run();
        }
    }

    /**
     *  Create table crawler if not exist
     */
    private function setup()
    {
        $sql = ' CREATE TABLE IF NOT EXISTS `crawler` ('
            .' `id` INT(11) AUTO_INCREMENT NOT NULL,'
            .' `posted` DATETIME NOT NULL,'
            .' `domain` VARCHAR(500) NOT NULL,'
            .' `url` TEXT NOT NULL,'
            .' `url_hash` VARCHAR (50) NOT NULL,'
            .' `description` TEXT,'
            .' `keywords` TEXT,'
            .' `title` TEXT,'
            .' PRIMARY KEY(`id`))';
        if ($this->database->ExecQuery($sql)) {
            $this->writeln('Setup Completed');
        } else {
            $this->writeln($this->database->error());
        }
    }

    /**
     * Check if all needed params exists
     */
    private function checkParams($params)
    {
        if (empty($params['d']) or empty($params['p'])) {
            $this->log('You loss some params');
            die();
        }
    }

    private function needToStop()
    {
        if ($this->countPages >= $this->pageLimit or ( $this->countResults >= $this->resultsLimit)) {
            return true;
        }
        return false;
    }

    private function setProtocol($protocol)
    {
        $this->protocol = $protocol;
    }

    private function setDomain($domain)
    {
        $this->domain = str_replace(['http://', 'https://'], ['', ''], $domain);
    }

    private function analyzeContent()
    {
        $this->findLinks();
        $this->findMetas();
        $this->findTitle();
    }

    /**
     * Find all meta tags from current page
     */
    private function findMetas()
    {
        $this->page['description'] = '';
        $this->page['keywords'] = '';
        $pattern = '
            ~<\s*meta\s
              # using lookahead to capture type to $1
                (?=[^>]*?
                \b(?:name|property|http-equiv)\s*=\s*
                (?|"\s*([^"]*?)\s*"|\'\s*([^\']*?)\s*\'|
                ([^"\'>]*?)(?=\s*/?\s*>|\s\w+\s*=))
            )
  # capture content to $2
  [^>]*?\bcontent\s*=\s*
    (?|"\s*([^"]*?)\s*"|\'\s*([^\']*?)\s*\'|
    ([^"\'>]*?)(?=\s*/?\s*>|\s\w+\s*=))
  [^>]*>

  ~ix';

        if (preg_match_all($pattern, $this->page['content'], $out)) {
            $metas = array_combine($out[1], $out[2]);
            if (!empty($metas['description'])) {
                $this->countResults++;
                $this->page['description'] = $metas['description'];
            }
            if (!empty($metas['keywords'])) {
                $this->page['keywords'] = $metas['keywords'];
            }
        }
    }

    /**
     * Find title tags from current page
     */
    private function findTitle()
    {
        $this->page['title'] = '';
        $pattern = '~<title[^>]*>([^<]+)<\/title>~si';

        if (preg_match($pattern, $this->page['content'], $matches)) {
            $this->page['title'] = $matches[1];
        }
    }

    /**
     * Find all links from current page
     */
    private function findLinks()
    {
        $pattern = '#<(a|area)(\s+?[^>]*?\s+?|\s+?)href\s*=\s*(["\'`]*)\s*?([^>\s]+)\s*\3[^>]*?(/>|>(.*?)</\1>|>)#is';
        preg_match_all($pattern, $this->page['content'], $matches, PREG_SET_ORDER);
        if (empty($matches)) {
            return;
        }
        foreach ($matches as $match) {
            $href = trim(str_replace('"', '', $match[4]));
            $preparedUrl = $this->prepareUrl($href);
            $this->addQueue($preparedUrl);
        }
    }

    /**
     * Checking if url not  foreign domain
     */
    private function checkUrl($url)
    {
        if (strpos($url, $this->domain) === false or empty($url)) {
            return false;
        }
        return true;
    }

    /**
     * Making full url with domain and protocol parts
     */
    private function prepareUrl($url)
    {
        if (preg_match('/^\/\//', $url)) {
            $url = str_replace('//', $this->protocol, $url);
        }

        if (mb_strlen(trim($url), 'utf-8') == 0) {
            return;
        }

        if (strpos($url, 'http://') === false and strpos($url, 'https://') === false) {//если относительная ссылка
            if ($url{0} !== '/') {//link not from root
                $url = $this->page['url'].'/'.$url;
            } else {// link from root
                $url = $this->protocol.$this->domain.'/'.ltrim($url, '/');
            }
        }
        return $url;
    }

    /**
     * Adding url to parse queue
     */
    private function addQueue($url)
    {
        if ($this->checkUrl($url)
            and ! isset($this->queue[md5(trim($url, '/'))])) {
            $this->queue[md5($url)] = $url;
        }
    }

    /**
     * Load url using curl
     */
    private function getUrl($url)
    {
        $curlId = curl_init();
        curl_setopt($curlId, CURLOPT_URL, $url);
        curl_setopt($curlId, CURLOPT_USERAGENT, $this->userAgent);
        curl_setopt($curlId, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curlId, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curlId, CURLOPT_REFERER, $this->domain);
        curl_setopt($curlId, CURLOPT_VERBOSE, false);
        curl_setopt($curlId, CURLOPT_HEADER, false);
        $page = curl_exec($curlId);
        $err = curl_error($curlId);

        if (!empty($err)) {
            sleep($this->timeout);
        }

        $page = preg_replace('/<!--.*-->/Uis', '', $page);
        $this->page['content'] = $page;
        $this->page['url'] = $url;
        $this->page['info'] = curl_getinfo($curlId);
        $this->page['error'] = $err;

        if (empty($err)) {
            $this->countPages++;
        }
        curl_close($curlId);
    }

    /**
     * Save url to database
     */
    private function saveUrl()
    {
        $sql = 'insert into crawler '
            .'(domain,url,url_hash,description,title,keywords,posted)'
            .' values ('
            .'"'.$this->domain.'",'
            .'"'.$this->page['url'].'",'
            .'"'.md5($this->page['url']).'",'
            .'"'.$this->page['description'].'",'
            .'"'.$this->page['title'].'",'
            .'"'.$this->page['keywords'].'",'
            .'"'.date('Y-m-d H:i:s').'")';

        if ($this->database->ExecQuery($sql)) {
            $this->write(', saved');
        } else {
            $this->write(', error saving - '.$this->database->error());
        }
    }

    private function write($str)
    {
        $this->log($str);
        echo $str;
    }

    private function writeln($str)
    {
        $this->log($str.PHP_EOL);
        echo PHP_EOL.$str;
    }

    private function log($str)
    {
        file_put_contents($this->logFilename, $str, FILE_APPEND);
    }
}
