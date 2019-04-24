<?php

namespace j\net\base;

/**
 * Interface ServerInterface
 * @package j\net\base
 */
interface ServerInterface {

	function setProtocol($protocol);

	function run($setting);

	function shutdown();
}