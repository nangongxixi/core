<?php

namespace j\log;

/**
 * Class Log
 * ```
 * - handle(callback, formatter), 日志处理回调
 * - processor, 日志处理
 * ```
 * @package j\log
 */
class Log extends Logger {

    protected $stack = [];
    protected $mask = self::ALL;
    protected $defer = false;

    /**
     * Log constructor.
     */
    public function __construct(){
        $this->init();
    }

    protected function init(){
        $this->pushHandle($this->getDefaultHandle());
        $this->registerDeferHandle();
    }

    protected function getDefaultHandle(){
        return function($record){
            if($this->mask & $this->getLevel($record['level'])){
                $this->dispose($record);
            }
        };
    }

    protected function registerDeferHandle(){
        if(!$this->defer){
            return;
        }

        register_shutdown_function(function () {
            // make sure "flush()" is called last when there are multiple shutdown functions
            register_shutdown_function([$this, 'flush'], true);
        });
    }


    public function setDefer(){
        $this->defer = true;
        $this->registerDeferHandle();
    }

    /**
     * @param mixed $mask
     */
    public function setMask($mask) {
        $this->mask = $mask;
    }


    protected function dispose($record, $real = false){
        if(!$real && $this->defer){
            $this->stack[] = $record;
        } else {
            echo $this->format($record);
        }
    }

    protected function format($item){
        if(!$item['message']){
            return "\n";
        }

        if(is_array($item['message'])){
            $message = var_export($item['message'], true);
        } elseif(is_object($item['message'])) {
            $message = get_class($item['message']);
        } else {
            $message = $item['message'];
        }

        return ""
            . date("m/d h:i:s", $item['time'])
            . " - [{$item['levelName']}]"
            . " - " . $message  . PHP_EOL;
    }

    /**
     * flush log
     */
    function flush() {
        foreach($this->stack as $item){
            $this->dispose($item, true);
        }
        $this->stack = [];
    }
}