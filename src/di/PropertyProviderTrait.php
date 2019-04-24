<?php

namespace j\di;

use Exception, Closure;

/**
 * Trait PropertyProviderTrait
 * @package j\di
 */
trait PropertyProviderTrait {

    /**
     * @var array
     */
    protected $propertyProviders = [];

    /**
     * @param $name
     * @param array|callback|object $config
     * @param boolean $append
     */
    public function regPropertyProvider($name, $config = null, $append = false){
        if(is_array($name)) {
            if(isset($name['_proxy'])){
                /**
                 * @example
                 * _proxy = [
                 *   'view' => ['js', 'html', 'css'],
                 * ]
                 */
                foreach($name['_proxy'] as $proxy => $keys){
                    foreach($keys as $key){
                        $name[$key] = ['proxy' => $proxy];
                    }
                }
                unset($name['_proxy']);
            }
            if($append) {
                $this->propertyProviders = $this->propertyProviders + $name;
            } else {
                $this->propertyProviders = array_merge($this->propertyProviders, $name);
            }
        } else {
            $this->propertyProviders[$name] = $config;
        }
    }

    protected function hasProperty($key){
        return isset($this->propertyProviders[$key]);
    }

    /**
     * @param $name
     * @return Object
     * @throws Exception
     */
    protected function loadPropertyObject($name) {
        if(($this instanceof ContainerAwareInterface) && $this->hasService($name)){
            // get from di
            return $this->getService($name);
        }

        if(!isset($this->propertyProviders[$name])){
            throw(new Exception("Invalid name({$name})"));
        }

        $define = $this->propertyProviders[$name];

        // property proxy
        if(is_array($define) && isset($define['proxy']) && ($proxy = $define['proxy'])){
            $object = $this->$proxy;
            if(!is_object($object)){
                throw(new Exception("Invalid proxy({$proxy})"));
            }
            if($object instanceof Container){
                return $object->get($name);
            }
            return $object->$name;
        }

        return Container::getInstance()->make($define);
    }

    /**
     * @param $name
     * @return mixed
     */
    protected function getPropertyObject($name){
        if(!isset($this->$name)){
            $this->{$name} = $this->loadPropertyObject($name);
        }
        return $this->$name;
    }
}