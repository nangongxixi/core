<?php

namespace j\error;

use \Exception as BaseException;
use j\debug\Debug;
use j\log\Logger;
use j\log\TraitLog;

/**
 * Class Reporter
 * @package j\error
 */
class Reporter {

    use TraitLog;

    public $enableErrorTrace = false;
    public $enableUrl = false;

    private $errorLevelMap;

    /**
     * @param $e
     * @param int $level
     */
    public function report($e, $level)
    {
        if(!isset($this->errorLevelMap)){
            $this->errorLevelMap = $this->defaultErrorLevelMap();
        }

        $logLevel =  isset($this->errorLevelMap[$level]) ? $this->errorLevelMap[$level] : 'info';
        $this->log($this->getExceptionLog($e), $logLevel);
    }

    protected function defaultErrorLevelMap()
    {
        return array(
            E_ERROR             => Logger::ERROR,
            E_WARNING           => Logger::WARNING,
            E_PARSE             => Logger::ERROR,
            E_NOTICE            => Logger::WARNING,
            Error::E_REPORT     => Logger::DEBUG,
        );
    }

    /**
     * 获取错误日志
     * @param BaseException $exception
     * @return string
     */
    protected function getExceptionLog($exception)
    {
        $message = sprintf('%s(%d):%s at %s(%d)',
            get_class($exception),
            $exception->getCode(),
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine()
            );

        if($this->enableErrorTrace){
            $message .= "\n" . $exception->getTraceAsString();
        }

        if($this->enableUrl && isset($_SERVER['HTTP_HOST'])){
            $message .= "\n";
            $message .= "http://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        }

        return $message;
    }
}