<?php

namespace j\net\base;

use j\debug\Debug;
use swoole_server;
use j\log\TraitLog;

/**
 * Class Server
 * @package j\net\base
 */
class Server implements ServerInterface{
    use TraitLog;

    /**
     * @var Protocol
     */
    public $protocol;

    public $host = '0.0.0.0';
    public $port;
    public $timeout;

    static $sw_mode = SWOOLE_PROCESS;

    /**
     * @var swoole_server
     */
    protected $sw;

    protected $setting = array(
        'worker_num' => 4,       //worker process num
        'backlog' => 128,        //listen backlog

        'open_length_check' => true,
        'package_length_type' => 'N',
        'package_length_offset' => 0,
        'package_body_offset' => 0,

        'open_tcp_keepalive' => 1,
        'heartbeat_check_interval' => 5,
        'heartbeat_idle_time' => 10,
        );

    function __construct($host, $port) {
        $this->host = $host;
        $this->port = $port;
        $this->init();
    }

    protected function init(){

    }

    function setOption($key, $value = null){
        if(is_array($key)){
            $this->setting = array_merge($this->setting, $key);
        } else {
            $this->setting[$key] = $value;
        }
    }

    function daemonize() {
        $this->setting['daemonize'] = 1;
    }

    /**
     * @param $protocol
     * @throws \Exception
     */
    function setProtocol($protocol){
        if (!($protocol instanceof InterfaceProtocol)){
            throw new \Exception("The protocol is not instanceof InterfaceProtocol");
        }
        $this->protocol = $protocol;
    }

    /**
     * @return Protocol
     * @throws \Exception
     */
    public function getProtocol() {
        if(isset($this->protocol) && is_object($this->protocol)){
            return $this->protocol;
        }

        if(!isset($this->protocol)){
            throw new \Exception("The protocol not found");
        }

        if(is_string($this->protocol)){
            $this->protocol = new $this->protocol();
            $this->protocol->server = $this;
        }

        return $this->protocol;
    }

    protected function createServer(){
        $this->sw = new swoole_server(
            $this->host, $this->port,
            self::$sw_mode, SWOOLE_SOCK_TCP
            );
        $this->sw->server = $this;
    }

    protected $isBoot = false;

    /**
     * @param array $setting
     * @throws
     */
    function run($setting = array()) {
        register_shutdown_function(array($this, 'handleFatal'));

        if(!isset($this->sw)){
            $this->createServer();
        }

        $this->setOption($setting);
        $this->checkConfig();

        // set options
        $this->sw->set($this->setting);

        // bind event
        $this->bind();

        // start server
        $this->sw->start();
    }

    public function checkConfig(){
    }

    /**
     * @throws \Exception
     */
    protected function bind(){
        $binds = [
            'onServerStart' => 'ManagerStart',
            'onServerStop' => 'ManagerStop',
            ];
        foreach($binds as $method => $evt){
            $this->sw->on($evt, array($this, $method));
        }

        $protocol = $this->getProtocol();
        $protocol->server = $this;
        $binds = [
            'onServerStart' => 'ManagerStart',
            'onServerStop' => 'ManagerStop',

            'onWorkerStart' => 'WorkerStart',
            'onWorkerStop' => 'WorkerStop',

            'onConnect' => 'Connect',
            'onReceive' => 'Receive',
            'onClose' => 'Close',

            'onTask' => 'Task',
            'onFinish' => 'Finish',
            'onRequest' => 'request',
            ];
        foreach($binds as $method => $evt){
            if(method_exists($protocol, $method)){
                $this->sw->on($evt, array($protocol, $method));
            }
        }
    }

    function onServerStart($serv){
        $this->log("Server start on {$this->host}:{$this->port}, pid {$serv->master_pid}");
        if (!empty($this->setting['pid_file'])){
            file_put_contents($this->setting['pid_file'], $serv->master_pid);
        }
    }

    function onServerStop(){
        $this->log("Server stop");
        if (!empty($this->setting['pid_file'])) {
            unlink($this->setting['pid_file']);
        }
    }

    /**
     * @return bool
     */
    function shutdown(){
        return $this->sw->shutdown();
    }

    function close($fd){
        return $this->sw->close($fd);
    }

    function addListener($host, $port, $type){
        $this->sw->addlistener($host, $port, $type);
    }

    function send($client_id, $data){
        return $this->sw->send($client_id, $data);
    }

    /**
     * catch error
     */
    function handleFatal(){
        // todo close current client
        if($log = Debug::traceError()) {
            $this->log($log, "error");
        }
    }
}