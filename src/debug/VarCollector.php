<?php

namespace j\debug;

defined('_JDEBUG')
    or define('_JDEBUG', get_cfg_var('env.name') == 'dev');

class VarCollector {
    static $ENABLE = _JDEBUG;
    static $LOG_FILE = '/tmp/debug.log';

    function save(){
        if(!self::$ENABLE){
            return;
        }

        $content = date('Y-m-d H:i:s') . "\n";
        $content .= var_export($this->marks, true) . "\n\n";
        file_put_contents(self::$LOG_FILE, $content, FILE_APPEND);
    }

    protected static $instance;
    protected static function getInstance(){
        if(!isset(self::$instance)){
            self::$instance = new self();
        }
        return self::$instance;
    }

    protected $marks = [];
    function add($message){
        $this->marks[] = $message;
    }

    public static function mark($message){
        if(!self::$ENABLE){
            return;
        }
        self::getInstance()->add($message);
    }
}