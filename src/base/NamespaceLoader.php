<?php

namespace j\base;

/**
 * Class NamespaceLoader
 * @package j\called
 */
class NamespaceLoader implements LoaderInterface{

    /**
     * @var array
     */
    protected  $ns = [];

    /**
     * NamespaceLoader constructor.
     * @param array $ns
     */
    public function __construct(array $ns = []) {
        $this->ns = $ns;
    }

    /**
     * @param $namespace
     */
    public function add($namespace){
        $this->ns[] = $namespace;
    }

    /**
     * @param array $ns
     */
    public function setNs($ns) {
        $this->ns = (array)$ns;
    }

    /**
     * psr4
     *  todo goods.list
     * @param $name
     * @return string|null
     */
    public function load($name){
        foreach($this->ns as $namespacePre){
            $class = $namespacePre . ucfirst($name);
            if(class_exists($class)){
                return $class;
            }
        }
        return null;
    }
}