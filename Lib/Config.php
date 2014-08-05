<?php

namespace Yasc;

/**
 * Class Config
 * Get all the config parameters
 *
 * @package Yasc
 */
class Config {
	/**
	 * @var array
	 */
	private $parameters;

	/**
	 * @param string $configFile Path to config file
	 */
	public function __construct($configFile) {
		if (!is_file($configFile)) {
			Log::writeAndDie("File '$configFile' doesn't exist");
		}
		$this->parameters = json_decode(file_get_contents($configFile), TRUE);
		if (empty($this->parameters)) {
			Log::writeAndDie("No configuration file loaded");
		}
	}

	/**
	 * @return string
	 */
	public function getStartUrl() {
		return $this->parameters['startUrl'];
	}

	/**
	 * @return string
	 */
	public function getDepth() {
		return (!empty($this->parameters['depth'])) ? $this->parameters['depth'] : 1;
	}

	/**
	 * @return string
	 */
	public function getDefaultHost() {
		return $this->parameters['defaultHost'];
	}

	/**
	 * @return array
	 */
	public function getUrlFiltersAllow() {
		return $this->parameters['urlFilters']['allow'];
	}

	/**
	 * @return array
	 */
	public function getUrlFiltersDisallow() {
		return $this->parameters['urlFilters']['disallow'];
	}

	/**
	 * Is solr mapping loaded?
	 *
	 * @return bool
	 */
	public function isSolr() {
		return (!empty($this->parameters['solr'])) ? TRUE : FALSE;
	}

	/**
	 * Is Match plugin loaded?
	 *
	 * @return bool
	 */
	public function isMatch() {
		return (!empty($this->parameters['match'])) ? TRUE : FALSE;
	}

	/**
	 * Return match values
	 *
	 * @return bool
	 */
	public function getMatchValues() {
		return $this->parameters['match'];
	}

	/**
	 * Is tika mapping loaded?
	 *
	 * @return bool
	 */
	public function isTika() {
		return (!empty($this->parameters['solr']['mappingTika'])) ? TRUE : FALSE;
	}

	/**
	 * @return string
	 */
	public function getSolrHost() {
		return $this->parameters['solr']['host'];
	}

	/**
	 * @return string
	 */
	public function getSolrPort() {
		return $this->parameters['solr']['port'];
	}

	/**
	 * @return string
	 */
	public function getSolrPath() {
		return $this->parameters['solr']['path'];
	}

	/**
	 * @return array
	 */
	public function getSolrMappingRegexp() {
		return $this->parameters['solr']['mapping']['regexp'];
	}

	/**
	 * @return array
	 */
	public function getSolrMappingStatic() {
		return $this->parameters['solr']['mapping']['static'];
	}

	/**
	 * @return array
	 */
	public function getSolrMappingSpecial() {
		return $this->parameters['solr']['mapping']['special'];
	}

	/**
	 * @return array
	 */
	public function getSolrMappingTikaStatic() {
		return $this->parameters['solr']['mappingTika']['static'];
	}

	/**
	 * @return array
	 */
	public function getSolrMappingTikaSpecial() {
		return $this->parameters['solr']['mappingTika']['special'];
	}

	/**
	 * @return int
	 */
	public function getRequestTimeout() {
		return (!empty($this->parameters['request']['timeout'])) ? $this->parameters['request']['timeout'] : 5;
	}

	/**
	 * @return string
	 */
	public function getRequestUseragent() {
		return (!empty($this->parameters['request']['useragent'])) ? $this->parameters['request']['useragent'] : 'Yasc';
	}

	/**
	 * @return int
	 */
	public function getRequestSleep() {
		return (!empty($this->parameters['request']['sleep'])) ? $this->parameters['request']['sleep'] : 0;
	}

	/**
	 * @return string
	 */
	public function getRequestAuthentication() {
		return (!empty($this->parameters['request']['authentication'])) ? $this->parameters['request']['authentication'] : NULL;
	}

	/**
	 * @return string
	 */
	public function getTikaPath() {
		return (!empty($this->parameters['solr']['tikaPath'])) ? $this->parameters['solr']['tikaPath'] : 'Plugins/Solr/Tika/tika-app-1.4.jar';
	}

	/**
	 * @return string
	 */
	public function getJavaPath() {
		return (!empty($this->parameters['solr']['javaPath'])) ? $this->parameters['solr']['javaPath'] : 'java';
	}

	/**
	 * @return string
	 */
	public static function getMemoryUsage() {
		$mem = (integer)((memory_get_usage() + 512) / 1024);
		$unit = array('b', 'kb', 'mb', 'gb', 'tb', 'pb');
		$i = floor(log($mem, 1024));
		return round($mem / pow(1024, $i), 2) . $unit[$i];
	}

}