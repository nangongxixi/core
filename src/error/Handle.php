<?php

namespace j\error;

use ErrorException;
use j\base\SingletonTrait;
use j\log\TraitLog;

/**
 * Class Handle
 * @package j\error
 */
class Handle {

    use SingletonTrait;
    use TraitLog;

    /**
     * @var bool
     */
    public $autoRenderException = true;

    /**
     * @var bool
     */
    public $enableLogErrorTrace = true;

    /**
     * @var Render
     */
    protected $render;

    /**
     * @var Reporter
     */
    protected $reporter;

    /**
     * @var bool
     */
    static $registered = false;

    /**
     * Handle constructor.
     * @param null $log
     * @throws
     */
    public function __construct($log = null){
        if($log){
            $this->setLogger($log);
        }
    }

    /**
     * @param $logger
     * @throws Exception
     */
    public function setLogger($logger){
        if(!is_object($logger)){
            throw new Exception("Invalid logger");
        }
        $this->logger = $logger;
        if(isset($this->reporter)){
            $this->reporter->setLogger($logger);
        }
    }

    /**
     * 注册错误处理
     * @param $callback
     * @param int $level
     * @param int $priority
     */
    public function addExceptionHandle($callback, $level = E_ERROR, $priority = 0){
        Error::setHandling($level, $callback, $priority);
    }

    /**
     * 注册系统错误处理
     */
    public function register() {
        if(static::$registered){
            return;
        } else {
            static::$registered = true;
        }

        // 注册系统handle
        $this->registerPhpHandle();

        // 注册全局错误处理回调
        Error::setHandling(E_ERROR, [$this, 'handleException']);
        Error::setHandling(E_WARNING + E_NOTICE, [$this, 'handleError']);
        Error::setHandling(Error::E_REPORT, function($e, $level){
            $this->report($e, $level);
        });
    }

    protected function registerPhpHandle(){
        register_shutdown_function(function(){
            $error = error_get_last();
            if (!is_null($error) && $this->isFatal($error['type'])) {
                $traceOffset = 0;
                $e = new FatalErrorException(
                    $error['message'], $error['type'], 0, $error['file'], $error['line'], $traceOffset
                );
                $this->handleException($e, E_ERROR);
            }
        });

        // 进入全局的错误处理
        set_error_handler(function($level, $errstr, $errFile, $errLine) {
            if (error_reporting() & $level) {
                $e = new ErrorException($errstr, $level, $level, $errFile, $errLine);
                Error::throwError($e, E_WARNING);
            }
        }, E_ALL);

        // 进入全局的错误处理
        set_exception_handler(function($exception){
            Error::handle($exception, E_ERROR);
            exit;
        });
    }

    protected function isFatal($type){
        return in_array($type, [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE]);
    }


    /**
     * 处理异常
     * @param Exception|FatalErrorException $error
     * @param int $level
     */
    public function handleException($error, $level){
        $this->report($error, $level);
        $this->render($error, $level);
    }

    /**
     * 处理普通错误
     * @param Exception $e
     * @param int $level
     */
    public function handleError($e, $level){
        $this->report($e, $level);
        $this->render($e, $level);
    }

    /**
     * 渲染错误
     * @param $error
     * @param $level
     */
    protected function render($error, $level = E_ERROR){
        if(!$this->autoRenderException){
            return;
        }

        if(!isset($this->render)){
            $this->render = new Render();
        }

        $this->render->render($error, $level);
    }

    /**
     * 记录错误
     * @param $e
     * @param int $level
     */
    protected function report($e, $level = E_ERROR){
        if(!isset($this->reporter)){
            $this->reporter = new Reporter();
            $this->reporter->enableErrorTrace = $this->enableLogErrorTrace;
            if(isset($this->logger)){
                $this->reporter->setLogger($this->logger);
            }
        }
        $this->reporter->report($e, $level);
    }
}
