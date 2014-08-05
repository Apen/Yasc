<?php

namespace Yasc;

use domDocument;

/**
 * Class Crawl
 *
 * @package Yasc
 */
class Crawl {

	/**
	 * Array with all the urls
	 *
	 * @var array $seenUrls
	 */
	private $seenUrls;

	/**
	 * Array with the urls stored by depth
	 * It is needded to resume an aborted crawling
	 *
	 * @var array $allUrls
	 */
	private $allUrls;

	/**
	 * Number of links to parse
	 *
	 * @var int $nbLinks
	 */
	private $nbLinks;

	/**
	 * @var \Yasc\Config $config
	 */
	protected $config;

	/**
	 * @var \Yasc\Request $request
	 */
	private $request;

	/**
	 * @var array $plugins
	 */
	protected $plugins;

	/**
	 * Init the config
	 */
	public function __construct() {
		$this->seenUrls = array();
		$this->nbLinks = 1;

		// init configuration via json
		$this->config = new Config($GLOBALS['argv'][1]);

		// request object
		$this->request = new Request($this->config);

		// Solr plugin
		$this->plugins['solr'] = new Solr($this->config);

		// Match plugin
		$this->plugins['match'] = new Match($this->config);
	}

	/**
	 * Start to crawl
	 */
	public function startCrawling() {
		$this->seenUrls[$this->config->getStartUrl()] = TRUE;
		$this->crawlPageIterative($this->config->getStartUrl(), $this->config->getDepth());
	}

	/**
	 * Crawl a page and subpages
	 *
	 * @param string $url
	 * @param int    $depth
	 */
	protected function crawlPage($url, $depth) {
		$response = $this->request->getContent($url);

		$this->debugCrawling($url, $depth, $response);

		$this->nbLinks--;

		if ($this->config->isSolr() === TRUE) {
			$this->plugins['solr']->getSolrFields($url, $response);
		}

		if ($depth > 0) {
			$links = $this->getLinks($response['data']);
			foreach ($links as $newurl) {
				$this->crawlPage($newurl, $depth - 1);
			}
		}

	}

	/**
	 * Crawl a page and subpages (iterative version)
	 * Save current crawling status to resume the crawling (very usefull)
	 *
	 * @param string $url
	 * @param int    $depth
	 */
	protected function crawlPageIterative($url, $depth) {
		$this->allUrls[$depth][$url] = '...';

		// load status file if needed
		if (is_file($this->getStatusJsonFile())) {
			$status = json_decode(file_get_contents($this->getStatusJsonFile()), TRUE);
			$this->allUrls = $status[0];
			$this->nbLinks = $status[1];
			Log::write('Loading status file : ' . $this->getStatusJsonFile());
		}

		while ($depth >= 0) {
			$depth--;
			$nbLinkForDepth = count($this->allUrls[$depth + 1]);
			$currentNumUrl = 1;
			foreach ($this->allUrls[$depth + 1] as $link => $status) {
				// if the url is not already parsed
				if ($status == '...') {
					$this->allUrls[$depth + 1][$link] = 'ok';
					$response = $this->request->getContent($link);

					$this->nbLinks--;

					$this->debugCrawling($link, $depth + 1, $response);
					Log::write('% of depth ' . ($depth + 1) . ' : ' . round((($currentNumUrl * 100) / $nbLinkForDepth), 2));

					$this->execPlugins($link, $response);

					if ($depth >= 0) {
						$newLinks = $this->getLinks($response['data']);
						foreach ($newLinks as $newLink) {
							$this->allUrls[$depth][$newLink] = '...';
						}
					}

					$this->writeStatusJsonFile();
				} else {
					$this->seenUrls[$link] = TRUE;
				}

				$currentNumUrl++;
			}

		}

	}

	/**
	 * Exec all the plugins on the current url
	 *
	 * @param string $url
	 * @param array  $response
	 */
	protected function execPlugins($url, $response) {
		if ($this->config->isSolr() === TRUE) {
			$this->plugins['solr']->getSolrFields($url, $response);
		}
		if ($this->config->isMatch() === TRUE) {
			$this->plugins['match']->matchValues($url, $response);
		}
	}

	/**
	 * Debug a url crawling
	 *
	 * @param string $url
	 * @param int    $depth
	 * @param array  $response
	 */
	protected function debugCrawling($url, $depth, $response) {
		Log::write('---------------------------------------------------------------------------------------------');

		if ($this->config->getRequestSleep() > 0) {
			Log::write('Sleeping : ' . $this->config->getRequestSleep() . ' seconds');
			sleep($this->config->getRequestSleep());
		}

		Log::write('Depth : ' . $depth);
		Log::write('Links to crawl : ' . $this->nbLinks);
		Log::write('Current memory : ' . Config::getMemoryUsage());
		Log::write('Current URL : ' . substr($url, 0, 200));
		Log::write('Response : content_type=' . $response['infos']['content_type'] . ' / http_code=' . $response['infos']['http_code'] . ' / parsed=' . $response['infos']['parsed'] . 's');
	}

	/**
	 * Check if a url is allowed
	 * - check regexp
	 * - check if already seen
	 *
	 * @param string $url
	 * @return bool
	 */
	public function checkLink($url) {
		if ($this->checkLinkDisallow($url) === FALSE) {
			return FALSE;
		}
		if ($this->checkLinkAllow($url) === FALSE) {
			return FALSE;
		}
		if (!empty($this->seenUrls[$url])) {
			return FALSE;
		}
		return TRUE;
	}

	/**
	 * Check if a link is allow by regexp
	 *
	 * @param string $url
	 * @return bool
	 */
	public function checkLinkAllow($url) {
		$check = FALSE;
		foreach ($this->config->getUrlFiltersAllow() as $filter) {
			if (preg_match('#' . $filter . '#si', $url, $matches) > 0) {
				$check = TRUE;
			}
		}
		return $check;
	}

	/**
	 * Check if a link is disallow by regexp
	 *
	 * @param string $url
	 * @return bool
	 */
	public function checkLinkDisallow($url) {
		$check = TRUE;
		foreach ($this->config->getUrlFiltersDisallow() as $filter) {
			if (preg_match('#' . $filter . '#si', $url, $matches) > 0) {
				$check = FALSE;
			}
		}
		return $check;
	}

	/**
	 * Normalize a url (absolute url)
	 *
	 * @param string $url
	 * @return string
	 */
	public function normalizeLink($url) {
		$newUrl = '';
		$parts = parse_url($url);

		// scheme
		if (empty($parts['scheme'])) {
			$newUrl .= 'http';
		} else {
			$newUrl .= $parts['scheme'];
		}
		$newUrl .= '://';

		// host
		if (empty($parts['host'])) {
			$newUrl .= $this->config->getDefaultHost();
		} else {
			$newUrl .= $parts['host'];
		}

		// path
		if (empty($parts['path'])) {
			$newUrl .= '/';
		} else {
			if ($parts['path']{0} == '/') {
				$parts['path'] = substr($parts['path'], 1);
			}
			$newUrl .= '/' . $parts['path'];
		}

		// query
		$newUrl .= empty($parts['query']) ? '' : '?' . $parts['query'];

		// clean url
		$newUrl = str_replace(' ', '%20', $newUrl);

		return $newUrl;
	}

	/**
	 * Get all links from html code
	 *
	 * @param string $htmlCode
	 * @return array
	 */
	public function getLinks($htmlCode) {
		$ret = array();
		$dom = new domDocument;
		@$dom->loadHTML($htmlCode);
		$dom->preserveWhiteSpace = FALSE;
		$links = $dom->getElementsByTagName('a');
		foreach ($links as $tag) {
			$url = $this->normalizeLink($tag->getAttribute('href'));
			if ($this->checkLink($url) === TRUE) {
				$this->seenUrls[$url] = TRUE;
				$ret[] = $url;
				//\Yasc\Log::write('Link allow : ' . substr($url, 0, 200));
			} else {
				//\Yasc\Log::write('Link disallow : ' . substr($url, 0, 200));
			}
		}
		$this->nbLinks = $this->nbLinks + count($ret);
		return $ret;
	}

	/**
	 * Write crawling logs
	 */
	public function writeLinks() {
		$urls = $this->getSeenUrls();

		// write txt file
		@unlink($this->getLinksTxtFile());
		$handle = fopen($this->getLinksTxtFile(), 'a');
		if (!$handle) {
			die("Can't open file " . $this->getLinksTxtFile());
		}
		foreach ($urls as $url) {
			fwrite($handle, $url . PHP_EOL);
		}
		fclose($handle);
	}

	/**
	 * Write crawling status
	 */
	protected function writeStatusJsonFile() {
		$handle = fopen($this->getStatusJsonFile(), 'w');
		if (!$handle) {
			die("Can't open file " . $this->getLinksJsonFile());
		}
		fwrite($handle, json_encode(array($this->allUrls, $this->nbLinks)));
		fclose($handle);
	}

	/**
	 * Get links txt file
	 *
	 * @return string
	 */
	protected function getLinksTxtFile() {
		return realpath(__DIR__ . '/../Crawl') . '/links.txt';
	}

	/**
	 * Get links json status file
	 *
	 * @return string
	 */
	protected function getStatusJsonFile() {
		return realpath(__DIR__ . '/../Crawl') . '/status.json';
	}

	/**
	 * Return all seen urls
	 *
	 * @return array
	 */
	public function getSeenUrls() {
		$urls = array_keys($this->seenUrls);
		sort($urls);
		return $urls;
	}

}