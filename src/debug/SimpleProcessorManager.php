<?php

namespace j\debug;

use swoole_process;

/**
 * Class SimpleProcessorManager
 * @package j\debug
 */
class SimpleProcessorManager{
    /**
     * @var array
     */
    protected $works = [];
    protected $workNums;

    protected function start($callback){
        for($i = 0; $i < $this->workNums; $i++) {
            $process = new swoole_process($callback, false, false);
            $pid = $process->start();
            $workers[$pid] = $process;
        }
    }

    function run($workNums, $callback){
        $this->workNums = $workNums;
        $this->start($callback);
        $this->close();
    }

    protected function close(){
        for($i = 0; $i < $this->workNums; $i++) {
            $ret = swoole_process::wait();
            $pid = $ret['pid'];
            echo "Worker Exit, PID=" . $pid . PHP_EOL;
        }
    }
}
