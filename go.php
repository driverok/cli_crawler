<?php
namespace driverok;

include_once 'db_config.php';
include_once 'db.php';
include_once 'crawler.php';

$crawler = new Crawler();
$crawler->timeout = 10; //time to sleep when error loading page
$crawler->logFilename = '/tmp/crawler.log'; //location of log file
$crawler->pageLimit = 100; //limit loaded pages
$crawler->resultsLimit = 10; //limit finded descriptions
$crawler->run();
