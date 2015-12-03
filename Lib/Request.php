<?php

namespace Yasc;

/**
 * Class Request
 *
 * @package Yasc
 */
class Request
{
    /** @var \Yasc\Config $config */
    private $config;

    /**
     * @param \Yasc\Config $config
     */
    public function __construct($config)
    {
        $this->config = $config;
    }

    /**
     * @param string $url
     * @return array
     */
    public function getContent($url)
    {
        $begin = microtime(true);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FILETIME, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->config->getRequestTimeout());
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->config->getRequestTimeout());
        curl_setopt($ch, CURLOPT_USERAGENT, $this->config->getRequestUseragent());
        $authentication = $this->config->getRequestAuthentication();
        if (!empty($authentication)) {
            curl_setopt($ch, CURLOPT_USERPWD, $authentication);
        }
		$headers = $this->config->getRequestHeaders();
		if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        $response = array();
        $response['data'] = curl_exec($ch);
        if ($response['data'] === false) {
            Log::write(curl_error($ch));
        }
        $response['infos'] = curl_getinfo($ch);
        $response['infos']['parsed'] = round((microtime(true) - $begin), 3);
        curl_close($ch);
        return $response;
    }

}