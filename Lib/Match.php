<?php

namespace Yasc;

/**
 * Class match
 *
 * @package Yasc
 */
class Match {

	/** @var \Yasc\Config $config */
	private $config;

	/**
	 * @param array $config
	 */
	public function __construct($config) {
		$this->config = $config;
	}


	/**
	 * Exec preg_match on teh response
	 *
	 * @param string $url
	 * @param array  $response
	 */
	public function matchValues($url, $response) {
		$matchValues = $this->config->getMatchValues();
		//\Yasc\Log::write($matchValues);
		foreach ($matchValues as $matchValue) {
			if (preg_match_all('#' . $matchValue . '#', $response['data'], $matches) > 0) {
				$this->writeMatchValues($matchValue, $url);
			}
		}
	}

	/**
	 * Write match url in file
	 *
	 * @param $matchValue
	 * @param $url
	 */
	protected function writeMatchValues($matchValue, $url) {
		// write txt file
		$handle = fopen($this->getMatchValuesTxtFile(), 'a');
		if (!$handle) {
			die("Can't open file " . $this->getMatchValuesTxtFile());
		}
		fwrite($handle, $matchValue . ' --> ' . $url . PHP_EOL);
		fclose($handle);
	}

	/**
	 * Get match values txt file
	 *
	 * @return string
	 */
	protected function getMatchValuesTxtFile() {
		return realpath(__DIR__ . '/../Crawl') . '/match.txt';
	}

}
