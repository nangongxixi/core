<?php

namespace j\net\serialize;

/**
 * Class EncoderPHP
 * @package j\net\base
 */
class EncoderPHP implements EncoderInterface {
	function encode($message) {
		return serialize($message);
	}

	function decode($message) {
		return unserialize($message);
	}
}
