<?php

namespace j\di;

/**
 * Class ContainerAwareTrait
 * @package j\di
 */
trait ContainerAwareTrait {
    /**
     * @var Container
     */
    public $container;

    /**
     * @param null $service
     */
    function setContainer($service = null){
        $this->container = $service;
        $this->containerCreated($service);
    }

    /**
     * @param Container $service
     */
    protected function containerCreated($service){

    }

    /**
     * @return bool
     */
    public function hasDi(){
        return isset($this->container);
    }

    function getService($key){
        if(!isset($this->container)){
            if(function_exists('service')){
                $container = service();
            } else {
                $container = Container::getInstance();
            }
            $this->setContainer($container);
        }

        if(func_num_args() > 1){
            return call_user_func_array(array($this->container, 'make'), func_get_args());
        } else {
            return $this->container->make($key);
        }
    }

    function hasService($name){
        return isset($this->container) && $this->container->has($name);
    }
}