<?php

namespace j\base;

/**
 * Interface ConfigurableInterface
 * @package j\base
 */
interface ConfigurableInterface {
    /**
     * @param array $properties
     * @return mixed
     */
    public function setProperties($properties);
}