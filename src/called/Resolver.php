<?php

namespace j\called;

use j\base\CallableInterface;
use j\base\LoaderInterface;

/**
 * Class Resolver
 * @package j\plug
 */
class Resolver {
    /**
     * @var LoaderInterface[]
     */
    protected $loaders =[];

    public $resolver;

    /**
     * @param LoaderInterface $loader
     */
    function addLoader(LoaderInterface $loader){
        $this->loaders[] = $loader;
    }

    function normalizeName($name){
        if(!$this->resolver){
            return $name;
        }
        return call_user_func($this->resolver);
    }

    /**
     * @param $name
     * @return CallableInterface|null|string|array
     */
    function load($name){
        $name = $this->normalizeName($name);
        foreach($this->loaders as $loader){
            if($plug = $loader->load($name)){
                return $plug;
            }
        }
        return null;
    }
}