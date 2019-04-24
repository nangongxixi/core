<?php

namespace j\error;

use Exception as BaseException;
use j\tool\Mask;

/**
 * Class Error
 * @package j\error
 */
final class Error {

    // 错误记录
    const E_REPORT = 8192;

    // 信息未找到
    const CODE_NOT_FOUND = 404;

    // 通用错误
    const CODE_E = 500;

    // 警告错误
    const CODE_W = 501;

    // 验证错误
    const CODE_VALID = 502;

    /**
     * @var array
     */
    protected static $levels = array(
        E_NOTICE => 'Notice',
        E_WARNING => 'Warning',
        E_ERROR => 'Error',
        self::E_REPORT => 'Info'
        );


    /**
     * 注册错误级别
     * @param $level
     * @param $name
     * @param callable $handler
     * @param int $priority
     * @return bool
     */
    public static function registerLevel($level, $name, $handler = null, $priority = 0){
        if (isset(self::$levels[$level])) {
            return false;
        }

        self::$levels[$level] = $name;
        if($handler){
            self::setHandling($level, $handler, $priority);
        }

        return true;
    }

    /**
     * @param $level
     * @return bool|mixed
     */
    public static function translateLevel($level){
        if (isset(self::$levels[$level])) {
            return self::$levels[$level];
        }
        return false;
    }

    /**
     * @var \SplPriorityQueue[]
     */
    protected static $handlers = [];

    /**
     * @var array
     */
    protected static $stack = [];

    /**
     * @param $msg
     */
    protected static function jexit($msg) {
        exit($msg);
    }

    /**
     * @param bool $unset
     * @param bool $toMap
     * @return Exception|array
     */
    public static function getError($unset = false, $toMap = false){
        $len = count(self::$stack);
        if($len == 0){
            return null;
        }

        if ($unset) {
            $error = array_pop(self::$stack);
        } else {
            $error = self::$stack[$len - 1];
        }

        return $toMap ? self::toMap($error) : $error;
    }

    public static function getErrors(){
        return self::$stack;
    }

    /**
     * @param BaseException $e
     */
    public static function addError(BaseException $e) {
        self::$stack[] = $e;
    }

    /**
     * @param null $object
     * @return bool
     */
    public static function isError($object = null){
        // supports PHP 5 exception handling
        if(is_object($object))
            return $object instanceof Exception;
        else
            return isset(self::$stack[0]);
    }

    /**
     * @param $msg
     * @param int $code
     * @param null $info
     */
    public static function error($msg, $code = self::CODE_E, $info = null) {
         self::raise(E_ERROR, $code, $msg, $info);
    }

    public static function warning($msg, $code = self::CODE_W, $info = null) {
         self::raise(E_WARNING, $code, $msg, $info);
    }

    public static function notice($msg, $code = self::CODE_W, $info = null){
         self::raise(E_NOTICE, $code, $msg, $info);
    }

    public static function report($msg, $code = self::CODE_W, $info = []){
         self::raise(self::E_REPORT, $code, $msg, $info);
    }

    /**
     * @param $error
     */
    public static function validError($error){
        Error::warning("数据验证失败", Error::CODE_VALID, $error);
    }

    /**
     * @param $level
     * @param $code
     * @param $msg
     * @throws
     * @param null $info
     */
    public static function raise($level, $code, $msg, $info = null){
        if($msg instanceof BaseException){
            $exception = $msg;
        } else {
            $exception = new Exception($msg, $code);
            $exception->setInfo($info);
            $exception->setLevel($level);
        }

        self::throwError($exception, $level);
    }

    private static $thrown = false;

    /**
     * @param Exception|BaseException $exception
     * @param null $level
     * @throws Exception
     */
    public static function throwError($exception, $level = null){
        $level = self::getExceptionLevel($exception, $level);
        if($level == E_ERROR){
            // 跳出正常流程
            throw $exception;
        } else {
            // add global error
            self::addError($exception);
            self::handle($exception, $level);
        }
    }

    private static function getExceptionLevel($exception, $level){
        if(isset($level) && $level){
           return $level;
        }

        if($exception instanceof Exception) {
            $level = $exception->getLevel();
        } else {
            $level = E_ERROR;
        }
        return $level;
    }

    /**
     * @param \Exception $exception
     * @param null $level
     */
    public static function handle($exception, $level = null){
        if (self::$thrown){
            echo $exception->getMessage();
            echo "\n";
            echo $exception->getTraceAsString();
            
            // 禁止递归调用
            //echo debug_print_backtrace();
            self::$thrown = false;
            self::jexit("ERROR_INFINITE_LOOP");
        }

        self::$thrown = true;

        // see what to do with this kind of error
        $level = self::getExceptionLevel($exception, $level);
        $handlers = isset(self::$handlers[$level]) ? self::$handlers[$level] : [];
        foreach($handlers as $callback){
            $rs = call_user_func($callback, $exception, $level);
            if($rs == true){
                break;
            }
        }

        self::$thrown = false;
    }


    /**
     * @var array
     */
    protected static $handlePriority = [];
    protected static $priority = 1;
    protected static $maxHandle = 100;

    /**
     * @param $level
     * @param $handler
     * @param int $priority
     */
    public static function setHandling($level, $handler, $priority = 0) {
        $levels = Mask::parse($level);
        foreach($levels as $level){
            if(!isset(self::$handlers[$level])){
                self::$handlers[$level] = [];
            }

            $index = count(self::$handlers[$level]);
            self::$handlePriority[$level][$index] = (static::$priority++) + $priority * self::$maxHandle;
            self::$handlers[$level][] = $handler;

            arsort(self::$handlePriority[$level]);
            $tmp = [];
            foreach(self::$handlePriority[$level] as $key => $i){
                $tmp[$key] = self::$handlers[$level][$key];
            }
            self::$handlers[$level] =  $tmp;
        }
    }


    /**
     * @param BaseException $e
     * @param bool $json
     * @param bool $debugTrace
     * @return array|string
     */
    public static function toMap($e, $json = false, $debugTrace = false){
        if(!($e instanceof BaseException)){
            $map = [
                'code' => 500,
                'data' => $e
                ];
        } else {
            $map = [
                'code' => ($code = $e->getCode()) ? $code : 500,
                'message' => $e->getMessage(),
                'data' => $e instanceof Exception ? $e->getInfo() : []
                ];
            if($debugTrace){
                $map['trace'] = explode("\n", $e->getTraceAsString());
            }
        }
        return $json ? json_encode($map) : $map;
    }
}