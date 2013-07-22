<?php

// example of execution
// php Yasc.php config.json

$begin = microtime(TRUE);

// load class
require_once('Lib/Config.php');
require_once('Lib/Log.php');
require_once('Lib/Crawl.php');
require_once('Lib/Request.php');
require_once('Lib/Solr.php');
require_once('Plugins/Solr/SolrPhpClient/Service.php');

// start crawling
$crawler = new \Yasc\Crawl();
$crawler->startCrawling();

// display find urls
\Yasc\Log::write('---------------------------------------------------------------------------------------------');
\Yasc\Log::write('Links parsed');
\Yasc\Log::write($crawler->getSeenUrls());
\Yasc\Log::write('---------------------------------------------------------------------------------------------');
\Yasc\Log::write('Parsed in ' . round((microtime(TRUE) - $begin), 2) . 's');
\Yasc\Log::write('---------------------------------------------------------------------------------------------');

