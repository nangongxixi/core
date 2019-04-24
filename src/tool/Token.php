<?php

namespace j\tool;

use j\cache\CacheInterface;

/**
 * Class Token
 * @package j\tool
 */
class Token {

    /**
     * @var CacheInterface
     */
    public $cache;
    protected $cacheKey;

    private $token;
    private $expireAt;
    private $genCallback;

    /**
     * Token constructor.
     * @param CacheInterface $cache
     * @param $cacheKey
     * @param $genCallback
     */
    public function __construct($genCallback, $cache = null, $cacheKey = null){
        $this->cache = $cache;
        $this->cacheKey = $cacheKey;
        $this->genCallback = $genCallback;
    }

    public $exireKey = 'expire';
    public $tokenkey = 'token';

    /**
     * @param bool $update
     * @return string
     */
    public function getToken($update = false) {
        // get cache
        if(isset($this->cache) && isset($this->cacheKey) && !$this->token){
            $data = $this->cache->get($this->cacheKey);
            if($data){
                $data = json_decode($data, true);
                $this->token = $data['token'];
                $this->expireAt = $data['expire'];
                //trace($data, 'token from cache');
            } else {
                $this->expireAt = 0;
                $this->token = '';
            }
        }

        if($update || !$this->token || $this->expireAt < time()){
            $this->genToken();
        }

        return $this->token;
    }

    private function genToken() {
        $result = call_user_func($this->genCallback);
        $this->setToken($result[$this->tokenkey], $result[$this->exireKey]);
    }

    function setToken($token, $expire = 0){
        $this->token = $token;
        $this->expireAt = $expire - 60 + time();
        // cache
        if($this->cache){
            $this->cache->set($this->cacheKey, json_encode([
                'expire' => $this->expireAt,
                'token' => $this->token
            ]), $expire);
        }
    }

    function clear(){
        // cache
        $cacheValue = json_encode([
            'expire' => 0,
            'token' => ''
        ]);

        if($this->cache){
            $this->cache->set($this->cacheKey, $cacheValue);
        }
    }

    function __toString(){
        return $this->getToken();
    }

    function toString(){
        return $this->getToken();
    }
}
