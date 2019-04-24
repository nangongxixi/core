<?php

namespace j\log;
use j\tool\Mask;

/**
 * Class Log
 * ```
 * - handle(callback, formatter), 日志处理回调
 * - processor, 日志处理
 * ```
 * @package j\log
 */
class Logger implements LogInterface {

    const INFO = 1;
    const WARNING = 2;
    const ERROR = 4;
    const DEBUG = 8;
    const ALL = 15;

    protected $handles = [];
    protected $processors = [];

    /**
     * @var array
     */
    static $levels = [
        self::INFO => 'info',
        self::WARNING => 'warning',
        self::ERROR => 'error',
        self::DEBUG => 'debug',
    ];

    public $name = 'default';

    /**
     * @param $level
     * @return false|int|string
     */
    protected function getLevel($level)
    {
        if(is_numeric($level)){
            return $level;
        }
        $level = array_search($level, self::$levels);
        return  $level === false ? self::INFO : $level;
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
     * @param int|string $level
     * @return $this
     */
    public function pushHandle($callback, $level = self::ALL)
    {
        if(is_numeric($level)){
            $levels = Mask::parse($level);
        } else {
            $levels = [$this->getLevel($level)];
        }

        foreach($levels as $level){
            if(!isset($this->handles[$level])){
                $this->handles[$level] = [];
            }

            array_unshift($this->handles[$level], $callback);
        }

        return $this;
    }

    public function pushHandler($callback, $level = self::ALL){
        return $this->pushHandle($callback, $level);
    }

    /**
     * Adds a processor on to the stack.
     *
     * @param callable $callback
     * @return $this
     */
    public function pushProcessor($callback)
    {
        if (!is_callable($callback)) {
            throw new \InvalidArgumentException('Processors must be valid callables (callback or object with an __invoke method), '.var_export($callback, true).' given');
        }
        array_unshift($this->processors, $callback);

        return $this;
    }

    /**
     * @param $message
     * @param string $type
     * @param array $context
     * @return $this
     */
    public function log($message, $type = 'info', $context = [])
    {
        if(is_string($type)){
            $level = $this->getLevel($type);
        } else {
            $level = $type;
        }

        if(!isset($this->handles[$level])){
            return $this;
        }

        $record = $this->makeRecord($message, $level, $context);
        foreach ($this->processors as $processor) {
            $record = call_user_func($processor, $record);
        }

        foreach($this->handles[$level] as $handle){
            if(!$handle($record, $this)){
                break;
            }
        }

        return $this;
    }

    protected function makeRecord($message, $level, $context)
    {
        return [
            'level' => $level,
            'levelName' => isset(self::$levels[$level]) ? self::$levels[$level] : 'unknown',
            'message' => $message,
            'time' => microtime(true),
            'context' => $context,
            'channel' => $this->name,
            'extra' => [],
        ];
    }

    public function logrotate() {}
}