<?php

namespace j\called;

use Exception;

use j\base\CallableInterface;
use j\base\ContextInterface;
use j\base\ConfigurableInterface;
use j\di\Container;

/**
 * 动态注入方法管理
 * Class CalledTrait
 * @package j\called
 */
trait CalledTrait {
    protected $__calls = [];
    protected static $__def = '__def';

    /**
     * @var Resolver
     */
    public  $runtimeLoader;

	/**
	 * @param string $m set __def if true
	 * @param $callback
	 * @param $checkExist
	 */
	public function regCall($m, $callback, $checkExist = false){
		if(true === $m){
			$m = static::$__def;
		}
		if(!$checkExist || !isset($this->__calls[$m])){
			$this->__calls[$m] = $callback;
		}
	}

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     * @throws Exception
     */
    public function call($name, $arguments = []){
        // auto load
        if(!isset($this->__calls[$name])){
            if(isset($this->runtimeLoader) &&
                ($callback = $this->runtimeLoader->load($name))
            ){
                // dynamic loading
                $this->__calls[$name] = $callback;
            } elseif(isset($this->__calls[static::$__def])){
                // use default
                $arguments = [$name, $arguments];
                $name = static::$__def;
            } else {
                throw new CallerNotFoundException("Caller($name) not found");
            }
        }

        // is object
        $callback = $this->__calls[$name];
        if(!is_callable($callback)){
            // string or array for object
            $callback = Container::getInstance()->make($callback, ['name' => $name]);
        }

        // todo 消除参数调用歧义
        if($callback instanceof \Closure
            || is_array($callback)
            || is_string($callback)
        ){
            // 1. function
            // this->call('name', [argument1, agument2])
            return call_user_func_array($callback, $arguments);
        }

        // object
        // register callback
        if($callback instanceof ContextInterface){
            $callback->setContext($this);
        }

        // if callback is object, must implements CallableInterface
        if($arguments && $callback instanceof ConfigurableInterface){
            // 配置请求参数
            // 2. call('name', [var1 => value1, ])
            $callback->setProperties($arguments);
        }

        return $callback($arguments);
    }

    /**
     * @param $method
     * @return bool
     */
    protected function isCallable($method){
        return isset($this->__calls[$method]);
    }
}