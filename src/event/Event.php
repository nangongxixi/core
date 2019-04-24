<?php

namespace j\event;

/**
 * Class Event
 * @package j\event
 */
class Event{

    public $context;
    protected $name;
    protected $props = [];

    /**
     * @var bool
     */
    protected $isPropagationStopped = false;

    /**
     * @param $name
     * @param [] $props
     */
    function __construct($name, $props = []) {
        $this->name = $name;
        if($props){
            $this->props = $props;
        }
    }

    /**
     * @return mixed
     */
    public function getName() {
        return $this->name;
    }

    /**
     * @return array
     */
    public function getProps() {
        return $this->props;
    }

    /**
     * @param $name
     * @param $value
     */
    public function setProps($name, $value) {
        $this->props[$name] = $value;
    }
    /**
     * @param null $pos
     * @return array|null
     */
    public function getArg($pos = null){
        if($pos === null){
            return $this->props;
        }
        return isset($this->props[$pos]) ? $this->props[$pos] : null;
    }

    /**
     * 停止事件循环
     * 1 中止整个运行
     * 2 中止当前事件继续循环
     */
    public function stopPropagation() {
        $this->isPropagationStopped = true;
    }

    /**
     * @return boolean
     */
    public function isPropagationStopped() {
        return $this->isPropagationStopped;
    }

    function __toString(){
        return $this->name;
    }
}
