<?php

namespace j\net\http;

use Exception;

/**
 * Class AsyncClient
 * @package j\http
 */
class AsyncClient{

    protected $base_fd;
    protected $result = [];
    protected $times = 0;
    protected $limit = 0;
    protected $callback;

    function __construct() {
        $this->base_fd = event_base_new();
    }

    /**
     * @param mixed $callback
     */
    public function setCallback($callback) {
        $this->callback = $callback;
    }

    /**
     * @param $host
     * @param null $callback
     * @throws Exception
     */
    function get($host, $callback = null){
        if(!$callback){
            $callback = $this->callback;
        }

        $uri = parse_url($host);
        $host = $uri['host'];
        $path = $uri['path'];

        $fd = stream_socket_client(
            "$host:80", $errno, $errstr, 3,
            STREAM_CLIENT_ASYNC_CONNECT | STREAM_CLIENT_CONNECT
        );
        if(!$fd){
            throw new Exception($errstr, $errno);
        }
        stream_set_blocking($fd, 0);

        // init event
        $base = $this->base_fd;
        $writeEvent = event_new();
        event_set($writeEvent, $fd,
            EV_WRITE,
            array($this, 'onAccept'),
            array($writeEvent, $base, $host, $path)
        );
        event_base_set($writeEvent, $base);
        event_add($writeEvent);

        $readEvent = event_new();
        event_set($readEvent, $fd,
            EV_READ | EV_PERSIST,
            array($this, 'onRead'),
            array($readEvent, $base, $callback)
        );
        event_base_set($readEvent, $base);
        event_add($readEvent);

        $this->times++;
    }

    function onAccept($socket, $events, $args){
        $out = "GET {$args[3]} HTTP/1.1\r\n";
        $out .= "Host: {$args[2]}\r\n";
        $out .= "Connection: Close\r\n\r\n";
        fwrite($socket, $out);
    }

    public function onRead($socket, $event, $args) {
        $i = intval($socket);
        if(!isset($this->result[$i])){
            $this->result[$i] = '';
        }

        while($chunk = fread($socket, 4096)) {
            $this->result[$i] .= $chunk;
        }

        if(feof($socket)) {
            $this->times--;

            fclose($socket);
            event_del($args[0]);
            $rs = $this->result[$i];
            unset($this->result[$i]);

            $rs = explode("\r\n\r\n", $rs, 2);
            call_user_func($args[2], $rs[1], $this);
        }
    }

    /**
     * @return int
     */
    public function getTimes() {
        return $this->times;
    }

    function loop(){
        event_base_loop($this->base_fd);
    }
}