<?php

namespace j\net\serialize;

/**
 * Interface Encoder
 * @package j\net\base
 */
interface EncoderInterface {
	function encode($message);
	function decode($message);
}