<?php

namespace j\di;

use Exception;
use Closure;
use ReflectionClass;

/**
 * Class Service
 * @package j\base
 */
class Container implements ServiceLocatorInterface {

    private static $instance;
    private $cache = array();

    protected $defines = array();
    protected $keys = array();
    protected $factory = [];

    /**
     * @var array
     */
    private $params;

    /**
     * @param string $namespace
     * @return static
     */
    public static function getInstance($namespace = 'default'){
        if(!isset(self::$instance[$namespace])){
            /** @var Container $service */
            $service = new static();
            self::$instance[$namespace] = $service;
        }

        return self::$instance[$namespace];
    }

    /**
     * @param $service
     * @param string $namespace
     */
    public static function regInstance($service, $namespace = 'default'){
        self::$instance[$namespace] = $service;
    }

    /**
     * @param array $params
     */
    public function setParams($params) {
        $this->params = $params;
    }

    /**
     * @param $name
     * @param null $obj
     * @param boolean $isFactory
     * @return $this
     */
    public function set($name, $obj = null, $isFactory = false){
        if(is_array($name)){
            $this->sets($name);
            return $this;
        }

        $this->defines[$name] = $obj;
        $this->keys[$name] = true;
        if($isFactory){
            $this->factory[$name] = true;
        }
        return $this;
    }

    public function sets($defines, $replace = true){
        $this->defines = $replace
            ? ($defines + $this->defines)
            : ($this->defines + $defines)
            ;
        $this->keys = array_keys($this->defines);
        $this->keys = array_flip($this->keys);
        return $this;
    }

    public function has($name){
        return isset($this->keys[$name]);
    }

    /**
     * @param $provider
     * @param null $options
     * @return mixed|Object
     * @throws Exception
     */
    public function make($provider, $options = null){
        if(is_string($provider) && $this->has($provider)){
            return $this->get($provider, $options);
        }

        if(!is_array($provider)){
            return $this->createObject($provider, $options);
        }

        if(isset($define['factory'])){
            // deprecated
            $options = isset($provider['params']) ? $provider['params'] : [];
            $provider = $provider['factory'];
        }elseif(!isset($provider['arguments'])
            && !isset($provider['calls'])
            && !isset($provider['property'])
        ){
            // 除class以外的key当做构造函数参数
            // 简化配置层级
            $class = $provider['class'];
            unset($provider['class']);
            $options = $provider;
            $provider = $class;
        } elseif(!isset($provider['arguments']) && is_array($options)){
            $provider['arguments'] = $options;
        }

        return $this->createObject($provider, $options);
    }

    /**
     * todo cache object with arguments
     * todo 解析构造函数
     * bug : if cached then args invalid
     * @param $name
     * @param mixed $options
     * @param boolean $cache
     * @return mixed
     * @throws
     */
    public function get($name, $options = null, $cache = true){
        if($cache && isset($this->cache[$name])){
            return $this->cache[$name];
        }

        if(!isset($this->keys[$name])){
            throw new ServiceNotFoundException("Invalid service({$name})");
        }

        $object = $this->createObject($this->defines[$name], $options);

        // extend
        foreach ($this->getExtenders($name) as $extender) {
            if($extend = $extender($object, $this, $options)){
                $object = $extend;
            }
        }

        if(!$cache || isset($this->factory[$name]) && $this->factory[$name]){
            return $object;
        }

        $this->cache[$name] = $object;
        return $object;
    }

    /**
     * todo: cache argument
     * @param string|callback|array $define
     * @param array $options
     * @return mixed|object
     * @throws Exception
     */
    protected function createObject($define, $options = null){
        if($define instanceof Closure || (is_array($define) && is_callable($define))){
            $instance = $define($this, $options);
        }elseif(is_object($define)){
            $instance =  $define;
        } else {
            // class
            if(is_string($define)){
                $className = $define;
                if(is_array($options)){
                    if(!isset($provider['arguments'])
                        && !isset($provider['calls'])
                        && !isset($provider['property'])
                    ){
                        $define = ['arguments' => $options];
                    } else {
                        $define = $options;
                    }
                } else {
                    $define = [];
                }
            }else if(is_array($define) && isset($define['class'])){
                $className = $define['class'];
            } else {
                throw new Exception("Invalid conf for di");
            }

            // arguments
            $instance = $this->newInstance(
                $className,
                isset($define['arguments']) ? $define['arguments'] : [],
                isset($define['property']) ? $define['property'] : [],
                isset($define['calls']) ? $define['calls'] : []
            );
        }

        if($instance instanceof ContainerAwareInterface && !$instance->hasDi()){
            $instance->setContainer($this);
        }

        return $instance;
    }


    protected function newInstance($className, $arguments = [], $property = [], $calls = []){
        if($arguments){
            $this->normalizesParams($arguments);
        }

        $reflection = new ReflectionClass($className);
        if($reflection->implementsInterface('j\base\SingletonInterface')){
            $instance = call_user_func(array($className, 'getInstance'), $arguments);
        }elseif($reflection->implementsInterface('j\base\ConfigurableInterface')){
            $instance = $reflection->newInstanceArgs([$arguments + $property]);
            unset($property);
        }else{
            $method = $reflection->getConstructor();
            if($method && $method->getNumberOfParameters() > 0){
                $instance = $reflection->newInstanceArgs($arguments);
            } else {
                $instance = $reflection->newInstanceArgs();
                foreach($arguments as $key => $_){
                    if(is_string($key)){
                        $instance->{$key} = $_;
                    }
                }
            }
        }

        // property
        if(isset($property) && is_array($property)){
            $this->normalizesParams($property);
            foreach($property as $key => $_){
                $instance->{$key} = $_;
            }
        }

        if($calls) {
            foreach($calls as $call){
                if(!is_array($call)){
                    $call = array($call);
                }
                $m = $call[0];
                if(isset($call[1]) && is_array($call[1])){
                    $params = $call[1];
                    $this->normalizesParams($params);
                } else {
                    $params = [];
                }
                call_user_func_array(array($instance, $m), $params);
            }
        }

        return $instance;
    }

    /**
     * The extension closures for services.
     *
     * @var array
     */
    protected $extenders = [];

    /**
     * Get the extender callbacks for a given type.
     *
     * @param  string  $abstract
     * @return array
     */
    protected function getExtenders($abstract){
        if (isset($this->extenders[$abstract])) {
            return $this->extenders[$abstract];
        }

        return [];
    }


    /**
     * "Extend" an abstract type in the container.
     *
     * @param  string    $abstract
     * @param  \Closure  $closure
     * @return void
     *
     * @throws \InvalidArgumentException
     */
    public function extend($abstract, Closure $closure){
        if(isset($this->cache[$abstract])) {
            if($object = $closure($this->cache[$abstract], $this)) {
                if(is_object($object)){
                    $this->cache[$abstract] = $object;
                }
            }
        }else{
            $this->extenders[$abstract][] = $closure;
        }
    }

    /**
     * @param $values
     * @throws Exception
     */
    protected function normalizesParams(& $values){
        foreach($values as $key => $val) {
            if(!is_string($val)){
                continue;
            }

            if(strncmp($val, '@', 1) === 0) {
                $name = substr($val, 1);
                if($this->has($name)){
                    $values[$key] = $this->get($name);
                }
            } elseif(preg_match_all('/%(.+?)%/', $val, $r)) {
                foreach ($r[1] as $match) {
                    if(!isset($this->params[$match])) {
                        throw new Exception("Invalid parameter");
                    }
                    $values[$key] = str_replace("%{$match}%", $this->params[$match], $val);
                }
            }
        }
    }

    /**
     * Registers a service provider.
     *
     * @param ServiceProviderInterface $provider A ServiceProviderInterface instance
     * @param array  $values   An array of values that customizes the provider
     *
     * @return static
     */
    public function register(ServiceProviderInterface $provider, array $values = array()){
        $provider->register($this);

        foreach ($values as $key => $value) {
            $this->set($key, $value);
        }

        return $this;
    }

    /**
     * @param [] $providers
     * @throws
     * @return $this
     */
    public function registerProviders($providers){
        foreach($providers as $provider){
            if($provider instanceof Closure){
                $provider = $provider($this);
            } elseif(!is_object($provider)) {
                $provider = $this->make($provider);
            }

            $provider->register($this);
        }
        return $this;
    }
}