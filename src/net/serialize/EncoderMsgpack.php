<?php

namespace j\net\serialize;

/**
 * Class EncoderMsgpack
 * @package j\net\base
 */
class EncoderMsgpack implements EncoderInterface {
	function encode($message) {
		return msgpack_pack($message);
	}

	function decode($message) {
		return msgpack_unpack($message);
	}
}