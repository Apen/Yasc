<?php

namespace Yasc;

use Apache_Solr_Service;
use Apache_Solr_Document;

/**
 * Class Solr
 *
 * @package Yasc
 */
class Solr {

	/**
	 * @var Apache_Solr_Service $solr
	 */
	private $solr;

	/** @var \Yasc\Config $config */
	private $config;

	/**
	 * @param array $config
	 */
	public function __construct($config) {
		$this->config = $config;

		// connect to solr if necessary
		if ($this->config->isSolr() === TRUE) {
			$this->solr = new Apache_Solr_Service($this->config->getSolrHost(), $this->config->getSolrPort(), $this->config->getSolrPath());
			if ($this->solr->ping()) {
				\Yasc\Log::write('Solr service responding [' . $this->config->getSolrHost() . ':' . $this->config->getSolrPort() . $this->config->getSolrPath() . ']');
				//$this->solr->deleteByQuery('appKey:yasc');
			} else {
				\Yasc\Log::writeAndDie('Solr service not responding');
			}
		}
	}

	/**
	 * Get all fields to index in Solr en commit them
	 *
	 * @param string $url
	 * @param array  $response
	 */
	public function getSolrFields($url, $response) {
		$solrFields = array();
		$htmlCode = $response['data'];

		// check if the url is text indexable
		// if not, sending doc to Tika
		if (preg_match('#text/*#', $response['infos']['content_type'], $matches) > 0) {
			$solrFields = $this->solrIndexPage($url, $response);
		} else {
			$solrFields = $this->solrIndexFile($url, $response);
		}

		//\Yasc\Log::write($solrFields);

		if (($this->config->isSolr() === TRUE) && (!empty($solrFields))) {
			if ($this->solr->ping()) {
				$begin = microtime(TRUE);
				$doc = new Apache_Solr_Document();
				foreach ($solrFields as $field => $value) {
					if (is_array($value)) {
						foreach ($value as $multiValue) {
							$doc->addField($field, $multiValue);
						}
					} else {
						$doc->addField($field, $value);
					}
				}
				try {
					\Yasc\Log::write('Solr : sending doc [' . substr($solrFields['title'] . ']', 0, 200));
					$solrResponse = $this->solr->addDocument($doc);
					$parsed = round(((microtime(TRUE) - $begin) * 1000));
					\Yasc\Log::write('Solr response : ' . $solrResponse->getHttpStatus() . ' ' . $solrResponse->getHttpStatusMessage() . ' / parsed=' . $parsed . 'ms');
					//\Yasc\Log::write($doc);
					$this->solr->commit();
					$this->solr->optimize();
				} catch (Exception $e) {
					echo $e->getMessage();
				}
			}
		}

	}

	/**
	 * Get all fields to index in Solr for a page
	 *
	 * @param string $url
	 * @param array  $response
	 */
	public function solrIndexPage($url, $response) {
		$solrFields = array();
		$htmlCode = $response['data'];

		// regexp fields
		foreach ($this->config->getSolrMappingRegexp() as $mapping) {
			$value = NULL;
			if (!empty($mapping['pregMatch'])) {
				if (preg_match('#' . $mapping['pregMatch'] . '#si', $htmlCode, $matches) > 0) {
					$value = $matches[1];
				}
			}
			if (!empty($mapping['pregMatchAll'])) {
				if (preg_match_all('#' . $mapping['pregMatchAll'] . '#si', $htmlCode, $matches) > 0) {
					$value = $matches[1];
				}
			}
			if (!empty($mapping['pregReplace'])) {
				foreach ($mapping['pregReplace'] as $replace) {
					$value = preg_replace('#' . $replace['from'] . '#', $replace['to'], $value);
				}
			}
			$solrFields[$mapping['solrField']] = $value;
			$solrFields[$mapping['solrField']] = $this->processFunctions($mapping, $solrFields[$mapping['solrField']]);
		}

		// static fields
		foreach ($this->config->getSolrMappingStatic() as $mapping) {
			$solrFields[$mapping['solrField']] = $mapping['solrValue'];
		}

		// special fields
		foreach ($this->config->getSolrMappingSpecial() as $mapping) {
			$solrFields[$mapping['solrField']] = $this->processSpecialMapping($url, $mapping, $response);
		}

		return $solrFields;
	}

	/**
	 * Get all fields to index in Solr for a file (with Tika)
	 *
	 * @param string $url
	 * @param array  $response
	 */
	public function solrIndexFile($url, $response) {
		$solrFields = array();

		if ($this->config->isTika() === TRUE) {

			// static fields
			foreach ($this->config->getSolrMappingTikaStatic() as $mapping) {
				$solrFields[$mapping['solrField']] = $mapping['solrValue'];
			}

			// special fields
			foreach ($this->config->getSolrMappingTikaSpecial() as $mapping) {
				$solrFields[$mapping['solrField']] = $this->processSpecialMapping($url, $mapping, $response);
			}

		}

		return $solrFields;
	}

	/**
	 * Process a special php function on a field
	 *
	 * @param array $mapping
	 * @param mixed $value
	 * @return mixed
	 */
	public function processFunctions($mapping, $value) {
		if (!empty($value)) {
			if (!empty($mapping['explode'])) {
				$value = explode($mapping['explode'], $value);
			}
			if (!empty($mapping['implode'])) {
				$value = implode($mapping['implode'], $value);
			}
			if (!empty($mapping['utf8htmlentitydecode'])) {
				$value = html_entity_decode($value, NULL, 'UTF-8');
			}
		}
		return $value;
	}

	/**
	 * Get special values compiled by Yasc
	 *
	 * @param string $url
	 * @param array  $mapping
	 * @param array  $response
	 * @return mixed
	 */
	public function processSpecialMapping($url, $mapping, $response) {
		$partsFile = pathinfo($url);
		$parts = parse_url($url);

		switch ($mapping['crawlerField']) {
			case 'URL':
				$value = $url;
				break;
			case 'URL_RELATIVE':
				$value = preg_replace('#^(://|[^/])+/#', '', $url);
				break;
			case 'HOST':
				$value = $parts['host'];
				break;
			case 'TITLE':
				$value = $partsFile['basename'];
				break;
			case 'CONTENT':
				if (preg_match('#text/*#', $response['infos']['content_type'], $matches) > 0) {
					$value = $response['data'];
				} else {
					$value = $this->extractUsingTika($url);
				}
				break;
		}

		return $value;
	}

	/**
	 * Extract content from a file with Tika
	 *
	 * @param string $file
	 * @return string
	 */
	public function extractUsingTika($file) {
		$tikaCommand = $this->config->getJavaPath() . ' -jar ' . escapeshellarg($this->config->getTikaPath()) . ' -eUTF8' . ' -t ' . escapeshellarg($file);
		$shellOutput = shell_exec($tikaCommand);
		//\Yasc\Log::write(array('command' => $tikaCommand, 'response' => $shellOutput));
		return $shellOutput;
	}

}
