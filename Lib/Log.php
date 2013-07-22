<?php

namespace Yasc;

/**
 * Class Log
 *
 * @package Yasc
 */
class Log {

	/**
	 * Write in console
	 *
	 * @param mixed $message
	 */
	public static function write($message) {
		if (is_array($message) || is_object($message)) {
			print_r($message);
		} else {
			echo $message . PHP_EOL;
		}
	}

	/**
	 * Write in console and quit
	 *
	 * @param mixed $message
	 */
	public static function writeAndDie($message) {
		self::write($message);
		die();
	}

}