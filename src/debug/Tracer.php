<?php

namespace j\debug;

use j\debugBar\DebugBar;

use j\log\TraitLog;

/**
 * Class Tracer
 * 变量调试
 *
 * @package j\debug
 */
class Tracer {

    use TraitLog;

    protected $handles = [];
    protected $enable = true;
    protected $isFirst = false;

    public $enableDebugTrace = true;
    public $defaultTraceLevel = 10;
    public $enableLevels = [];

    /**
     * Tracer constructor.
     * @param null $logger
     */
    public function __construct($logger = null)
    {
        if($logger){
            $this->setLogger($logger);
        }

        $this->init();
    }


    protected function init()
    {
        $this->pushHandle($this->getDefaultHandle());
    }

    protected function getDefaultHandle()
    {
        return function($record){
            $this->dispose($record);
        };
    }

    /**
     * @param bool $enable
     */
    public function setEnable($enable){
        $this->enable = $enable;
    }

    /**
     * @param array $handles
     */
    public function setHandles($handles)
    {
        $this->handles = $handles;
    }

    /**
     * @param $callback
     * @return $this
     */
    public function pushHandle($callback)
    {
        array_unshift($this->handles, $callback);
        return $this;
    }

    protected function dispose($item)
    {
        if(!$this->isFirst){
            $this->isFirst = true;
            $content = (isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'cli');
            $content .= (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '');
            $this->log($content, 'debug');
        }

        $content = "";
        if(isset($item['title'])){
            $content .=  "Title:{$item['title']}\n";
        }

        $var = $item['var'];
        if(is_object($var)){
            $content .= get_class($var);
        } else {
            $content .= var_export($var, true);
        }
        $content .= "\n";


        $this->log($content . $item['trace'], 'debug');
    }

    function trace($var, $group = 'debug', $context = [], $startTrace = 0)
    {
        if(!$this->enable){
            return;
        }

        if($this->enableLevels && !isset($this->enableLevels[$group])){
            return;
        }

        $item = [
            'var' => $var,
            'group' => $group,
            'context' => $context,
            'trace' => null,
            ];

        if($this->enableDebugTrace && $startTrace > -1){
            $item['trace'] = Debug::trace(
                isset($item['stack'])
                    ? $item['stack']
                    : $this->defaultTraceLevel
                , $startTrace
            );
        }

        foreach($this->handles as $handle){
            if(!$handle($item, $this)){
                break;
            }
        }
    }
}