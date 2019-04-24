<?php

namespace j\base;

/**
 * Class OptionsTrait
 * @package j\base
 */
trait OptionsTrait {

    private $__Options = [];

    function setOption($key, $value = null){
        if(is_array($key)){
            if($value === true){
                $this->__Options = $key;
            } else {
                $this->__Options = array_merge($this->__Options, $key);
            }
        } else {
            $this->__Options[$key] = $value;
        }
    }

    function getOption($key = null, $default = null){
        if(null === $key){
            return $this->__Options;
        }

        return array_key_exists($key, $this->__Options) ?  $this->__Options[$key] : $default;
    }

    function hasOption($key){
        return array_key_exists($key, $this->__Options);
    }
}