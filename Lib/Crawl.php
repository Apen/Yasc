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
		$this->config = new \Yasc\Config($GLOBALS['argv'][1]);

		// request object
		$this->request = new \Yasc\Request($this->config);

		// Solr plugin
		$this->plugins['solr'] = new \Yasc\Solr($this->config);
	}

	/**
	 * Start to crawl
	 */
	public function startCrawling() {
		$this->seenUrls[$this->config->getStartUrl()] = TRUE;
		$this->crawlPage($this->config->getStartUrl(), $this->config->getDepth());
	}

	/**
	 * Crawl a page and subpages
	 *
	 * @param string $url
	 * @param int    $depth
	 */
	protected function crawlPage($url, $depth) {
		$response = $this->request->getContent($url);

		\Yasc\Log::write('---------------------------------------------------------------------------------------------');

		if ($this->config->getRequestSleep() > 0) {
			\Yasc\Log::write('Sleeping : ' . $this->config->getRequestSleep() . ' seconds');
			sleep($this->config->getRequestSleep());
		}

		\Yasc\Log::write('Depth : ' . $depth);
		\Yasc\Log::write('Links to crawl : ' . $this->nbLinks);
		\Yasc\Log::write('Current memory : ' . \Yasc\Config::getMemoryUsage());
		\Yasc\Log::write('Current URL : ' . substr($url, 0, 200));
		\Yasc\Log::write('Response : content_type=' . $response['infos']['content_type'] . ' / http_code=' . $response['infos']['http_code'] . ' / parsed=' . $response['infos']['parsed'] . 's');

		$this->nbLinks--;

		$response = $this->request->getContent($url);
		if ($this->config->isSolr() === TRUE) {
			$this->plugins['solr']->getSolrFields($url, $response);
		}

		if ($depth > 0) {
			$links = $this->getLinks($response['data']);
			$this->nbLinks = $this->nbLinks + count($links);
			foreach ($links as $newurl) {
				$this->crawlPage($newurl, $depth - 1);
			}
		}

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
		return $ret;
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