<?php

namespace j\base;

/**
 * Interface LoaderInterface
 */
interface LoaderInterface{
    /**
     * @param $name
     * @return mixed
     */
    function load($name);
}