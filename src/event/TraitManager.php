<?php

namespace j\event;

use j\di\Container;

/**
 * Class TraitManager
 * @package j\event
 */
trait TraitManager {

    protected $__eHandles= [];
    protected $__eHandleId = [];
    protected $__initBehaviors = false;

    /**
     * @param InterfaceListen $listener
     * @return $this
     */
    public function attaches($listener){
        $listener->bind($this);
        return $this;
    }

    /**
     * @param string $name
     * @param $callback
     * @param int $priority
     * @param string $id
     */
    public function on($name, $callback, $priority = 0, $id = ''){
        if(!isset($this->__eHandles[$name])){
            $this->__eHandles[$name] = [];
        }

        if(!is_int($priority)){
            // compatible old version
            if(is_int($id)){
                list($priority, $id) = [$id, $priority];
            } else {
                $id = $priority;
                $priority = 0;
            }
        }
        $index = count($this->__eHandles[$name]) + $priority * 100;

        $this->__eHandles[$name][$index] = $callback;
        $this->__eHandleId[$name][$index] = $id;
        krsort($this->__eHandleId[$name]);
        //trace($this->__eHandleId, 'set event ' . $index, 'event');
    }

    public function off($name = null) {
        if(!$name){
            $this->__eHandles = [];
        }else{
            unset($this->__eHandles[$name]);
        }
    }

    public function isBind($name){
        return isset($this->__eHandles[$name]) && !empty($this->__eHandles[$name]);
    }

    /**
     * @return InterfaceListen[]
     */
    protected function behaviors(){
        return [];
    }

    /**
     * @param $event
     * @throws
     * @return EventResult
     */
    public function trigger($event) {
        if(!$this->__initBehaviors){
            $this->__initBehaviors = true;
            foreach($this->behaviors() as $name => $behavior){
                $behavior = Container::getInstance()->make($behavior);
                $behavior->bind($this);
            }
        }

        $args = func_get_args();

        // init event, result
        if(!($event instanceof Event)){
            $event = new Event($event);
            $event->context = $this;
            $args[0] = $event;
        }

        $result = new EventResult();
        $result->setEvent($event);

        // todo
        // global event trigger

        // run callback
        $name = $event->getName();
        if(isset($this->__eHandles[$name])){
            foreach($this->__eHandles[$name] as $index => $callback){
                if($event->isPropagationStopped()){
                    break;
                }

                // todo log callback
                //$id = $this->__eHandleId[$name][$index];
                //trace(get_class($this) . ":" . $event->getName() . ':' . $id, 'run', 'event');

                // execute handle
                $result->add(call_user_func_array($callback, $args));
            }
        }

        return $result;
    }
}
