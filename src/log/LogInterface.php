<?php

namespace j\log;

/**
 * Interface LogInterface
 * @package j\log
 */
interface LogInterface{

    /**
     * @param $message
     * @param string $type
     * @param mixed $context
     * @return mixed
     */
    public function log($message, $type = 'info', $context = null);

    /**
     * @return mixed
     */
    public function logrotate();
}