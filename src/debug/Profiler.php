<?php

namespace j\debug;

use XHProfRuns_Default;

/**
 * Class Profiler
 * @package j\debug
 */
class Profiler {

    public static $authShowLink = true;
    public static $logPath = "/opt/tmp/";
    public static $url = "/xhprof_html/index.php?run=%s&&source=xhprof";

    static function register() {
        self::start();
        register_shutdown_function(function(){
            $showLink = PHP_SAPI == 'cli' ? false : self::$authShowLink;
            self::stop($showLink);
        });
    }

    static function start(){
        if(function_exists('tideways_disable')){
//            tideways_enable(TIDEWAYS_FLAGS_NO_SPANS);
            \tideways_enable(TIDEWAYS_FLAGS_CPU + TIDEWAYS_FLAGS_MEMORY);
        } elseif(function_exists('xhprof_enable')) {
            \xhprof_enable(XHPROF_FLAGS_CPU + XHPROF_FLAGS_MEMORY);
        }
    }

    /**
     * @param bool $showLink
     * @return mixed|string
     */
    static function stop($showLink = false){
        if(function_exists('tideways_disable')){
            $data = tideways_disable();
            $id = uniqid();
            $logDir = self::$logPath;
            if(!file_exists($logDir)){
                mkdir($logDir);
            }

            file_put_contents(
                $logDir . $id . ".xhprof.xhprof",
                serialize($data)
            );
        } elseif(function_exists('xhprof_disable')){
            $data = \xhprof_disable();   //返回运行数据
            $objXhprofRun = new XHProfRuns_Default();
            $id = $objXhprofRun->save_run($data, "xhprof");
        } else {
            return '';
        }

        if($showLink){
            echo self::showLink($id);
        }
        return $id;
    }

    private static function showLink($id){
        $url = sprintf(self::$url, $id);
        return '<!--' . $url . '!-->';
    }
}