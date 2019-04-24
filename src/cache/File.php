<?php

namespace j\cache;

use j\error\Exception;

/**
 * Class Cache
 */
class File implements CacheInterface{

    /**
     * @var array
     */
    protected $config = [];

    /**
     * @var array|mixed
     */
    protected $data = [];

    /**
     * Cache constructor.
     * @param array $config
     * @throws \j\error\Exception
     */
    public function __construct(array $config = []){
        if(!isset($config['file'])){
            throw new Exception("Invalid file for cache");
        }

        if(isset($config['data'])){
            $this->data = array_merge($this->data, $config['data']);
        }

        if(isset($config['expire'])){
            $this->config['expire'] = Base::DEFAULT_EXPIRE;
        }

        if(file_exists($config['file'])){
            if(!is_writable($config['file'])){
                throw new Exception("File can not write");
            }
            $data = file_get_contents($config['file']);
            if($data && ($data = unserialize($data))){
                $this->data = $data;
            }
        } else {
            $dir = dirname($config['file']);
            if(!is_dir($dir) || !is_writable($dir)){
                throw new Exception("Cache dir can not write");
            }
        }

        $this->config = $config;
    }

    public function get($id, $default = NULL){
        if(!isset($this->data[$id])){
            return $default;
        }

        if($this->data[$id]['expire'] < time()){
            unset($this->data[$id]);
            $this->save();
            return $default;
        }

        return $this->data[$id]['value'];
    }

    public function set($id, $data, $lifetime = 3600){
        $this->data[$id] = [
            'value' => $data,
            'expire' => time() + $lifetime
        ];
        $this->save();
    }

    protected function save(){
        file_put_contents($this->config['file'], serialize($this->data));
    }

    public function delete($id, $timeout = 0){
        unset($this->data[$id]);
        $this->save();
    }

    public function deleteAll(){
        $this->data = [];
        $this->save();
    }
}