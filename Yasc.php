<?php

// example of execution
// php Yasc.php config.json

$begin = microtime(true);

// load class
require_once('Lib/Config.php');
require_once('Lib/Log.php');
require_once('Lib/Crawl.php');
require_once('Lib/Request.php');
require_once('Lib/Solr.php');
require_once('Lib/Match.php');
require_once('Plugins/Solr/SolrPhpClient/Service.php');

// start crawling
$crawler = new \Yasc\Crawl();
$crawler->startCrawling();

// write links datas
$crawler->writeLinks();

// display find urls
\Yasc\Log::write('---------------------------------------------------------------------------------------------');
\Yasc\Log::write('Links parsed');
\Yasc\Log::write(count($crawler->getSeenUrls()));
\Yasc\Log::write('---------------------------------------------------------------------------------------------');
\Yasc\Log::write('Parsed in ' . round((microtime(true) - $begin), 2) . 's');
\Yasc\Log::write('---------------------------------------------------------------------------------------------');

