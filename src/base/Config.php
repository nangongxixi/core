<?php

namespace j\base;

use Yaconf;

/**
 * Class Config
 * @package j\base
 */
class Config {
    /**
     * @var array
     */
    private $props = [];

    /**
     * @var self[]
     */
    private static $instance;

    /**
     * @var array
     */
    protected $autoLoadDir = [];
    protected $autoloadNs = [];

    private $nsLoadedCache = [];
    private $dirLoadedCache = [];

    protected $enableYaconf = false;
    protected $keyExistsMap = [];

    /**
     * Constructor.
     * @param array|null $values  If $values == null, then the object will be initialized empty.
     * If it contains a valid PHP array, all the properties will be initialized at once.
     */
    public function __construct($values = null ) {
        if( $values && is_array($values)){
            $this->sets($values);
        }
    }

    function initFromDir($dir, $initNs = 'config', $appendDir = false){
        if($appendDir){
            $this->addAutoLoadDir($dir);
        } else {
            $this->setAutoLoadDir($dir);
        }

        if($initNs){
            $this->loadPhpArrayFromDir($initNs, function($data){
                $this->sets($data);
            });
        }

        $autoNs = $this->get('config.autoNs', []);
        if($autoNs){
            $this->setAutoloadNs($autoNs);
        }

        if($this->get('config.enable_yaconf')){
            $this->setEnableYaconf(true);
        }

        return $this;
    }

    /**
     * @param array $autoLoadDir
     * @return $this
     */
    public function setAutoLoadDir($autoLoadDir) {
        $this->autoLoadDir = $autoLoadDir;
        $this->resetLoadedNsCache();
        return $this;
    }

    /**
     * @param array $autoLoadDir
     * @return $this
     */
    public function addAutoLoadDir($autoLoadDir) {
        foreach((array)$autoLoadDir as $dir){
            $this->autoLoadDir[] = rtrim($dir, '\/') . "/";
        }
        $this->resetLoadedNsCache();
        return $this;
    }

    private function resetLoadedNsCache(){
        $this->nsLoadedCache = [];
    }

    /**
     * @param array $autoloadNs
     */
    public function setAutoloadNs($autoloadNs){
        $this->autoloadNs = $autoloadNs;
    }


    public function addAutoLoadNs($ns){
        if(!is_array($ns)){
            $ns = func_get_args();
        }
        $this->autoloadNs = array_merge($this->autoloadNs, $ns);
        return $this;
    }

    /**
     * @param boolean $enableYaconf
     */
    public function setEnableYaconf($enableYaconf) {
        if($enableYaconf){
            if(class_exists('Yaconf')){
                $this->enableYaconf = true;
            } else {
                trigger_error("Yaconf class not found");
                $this->enableYaconf = false;
            }
        } else {
            $this->enableYaconf = false;
        }
    }

    /**
     * @param string $namespace
     * @return self
     */
    public static function getInstance($namespace = 'default'){
        if(!isset(self::$instance[$namespace])){
            self::$instance[$namespace] = new static();
        }
        return self::$instance[$namespace];
    }

    /**
     * @param $key
     * @param null $value
     * @param boolean $append 是否追加
     * @return $this
     */
    public function set( $key, $value = null, $append = true ){
        if(is_array($key)){
            return $this->sets($key);
        }

        if(!$append){
            $this->clear($key); // 重置
        }

        if(strpos($key, '.')){
            list($ns, $name) =  explode('.', $key, 2);
        } else {
            $ns = $key;
        }

        $this->loadNs($ns);
        $this->changeItem($key, $value, $this->props);
        return $this;
    }

    /**
     * @param array $config
     * @param bool $append
     * @return $this
     */
    public function sets($config, $append = true){
        foreach($config as $k => $value){
            $this->set($k, $value, $append);
        }
        return $this;
    }

    private function loadNs($ns){
        if(isset($this->nsLoadedCache[$ns])
            || !$this->autoLoadDir
            || !in_array($ns, $this->autoloadNs)
        ){
            return;
        }

        $this->nsLoadedCache[$ns] = true;
        $this->loadPhpArrayFromDir($ns);
    }


    /**
     * todo support ini/yaml
     * @param $ns
     * @param callable $callback
     */
    private function loadPhpArrayFromDir($ns, $callback = null){
        foreach((array)$this->autoLoadDir as $dir) {
            $key = md5($ns . $dir);
            if(isset($this->dirLoadedCache[$key])){
                continue;
            } else {
                $this->dirLoadedCache[$key] = true;
            }

            $file = $dir . $ns . ".php";
            if(!is_readable($file) || !is_file($file)){
                continue;
            }

            $items = include($file);
            if(!is_array($items)){
                continue;
            }

            if($callback) {
                $callback($items);
            } else {
                $this->changeItem($ns, $items, $this->props);
            }
        }
    }

    protected function changeItem($key, $value, & $parent){
        if(strpos($key, '.')){
            list($ns, $name) = explode('.', $key, 2);
            if(!isset($parent[$ns]) || !is_array($parent[$ns])){
                $parent[$ns] = array();
            }
            if(strpos($name, '.') || is_array($value)){
                $this->changeItem($name, $value, $parent[$ns]);
            } else {
                $parent[$ns][$name] = $value;
            }
        } else {
            if(is_array($value) && isset($value['_alone'])){
                unset($value['_alone']);
                $parent[$key] = $value;
            }elseif(is_numeric($key)){
                $parent[] = $value;
            } elseif(is_array($value)){
                if(!isset($parent[$key]) || !is_array($parent[$key])){
                    $parent[$key] = array();
                }
                foreach($value as $_k => $_v){
                    if(strpos($_k, '.')){
                        $this->changeItem($_k, $_v, $parent[$key]);
                    }elseif(is_array($_v) && isset($_v['_alone'])){
                        unset($_v['_alone']);
                        $parent[$key][$_k] = $_v;
                    }elseif(is_numeric($_k)){
                        $parent[$key][] = $_v;
                    } elseif(is_array($_v)){
                        $this->changeItem($_k, $_v, $parent[$key]);
                    } else {
                        $parent[$key][$_k] = $_v;
                    }
                }
            } else {
                $parent[$key] = $value;
            }
        }
    }

    /**
     * Returns the value associated to a key
     *
     * @param $key string Key whose value we want to fetch
     * @param $defaultValue string value that we should return in case the one we're looking for
     * is empty or does not exist
     * @return string|array|object, Value associated to that key
     */
    public function get( $key, $defaultValue = null ){
        if(strpos($key, '.')){
            list($ns, $name) =  explode('.', $key, 2);
        } else {
            $ns = $key;
        }
        $this->loadNs($ns);

        $value = $this->getItem($key);
        if(isset($value)){
            return $value;
        }

        if($this->enableYaconf && Yaconf::has($key)) {
            return Yaconf::get($key, $defaultValue);
        }

        return $defaultValue;
    }

    protected function getItem($key, $checkKey = false){
        $keys = explode('.', $key);
        $parent = $this->props;
        while($key = array_shift($keys)){
            if(!isset($parent[$key])){
                return $checkKey ? false : null;
            }
            if(!$keys){
                return $checkKey ? true : $parent[$key];
            }
            $parent = $parent[$key];
        }
        return null;
    }

    /**
     *
     * @param $key
     * @param $checkYaconf
     * @return bool
     */
    public function has($key, $checkYaconf = true){
        if($this->getItem($key, true)){
            return true;
        } elseif($checkYaconf && $this->enableYaconf){
            return Yaconf::has($key);
        } else {
            return false;
        }
    }

    /**
     * @param $key
     * @return $this
     */
    public function clear($key){
        if($this->has($key, false)){
            $this->set($key, null);
        }
        return $this;
    }
}