<?php

namespace j\base;

/**
 * Class SingletonTrait
 * @package j\base
 */
trait SingletonTrait {
    /**
     * @var static
     */
    protected static $instance;

    /**
     * @return static
     */
    public static function getInstance() {
        if (!isset(static::$instance)) {
            static::$instance = new static();
        }

        return static::$instance;
    }
}
