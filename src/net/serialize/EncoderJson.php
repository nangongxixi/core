<?php

namespace j\net\serialize;

/**
 * Class EncoderJson
 * @package j\net\base
 */
class EncoderJson implements EncoderInterface {
	function encode($message) {
		return json_encode($message);
	}

	function decode($message) {
		return json_decode($message, true);
	}
}