<?php

namespace j\cache;

use Exception as JException;

/**
 * Class Base
 * @package j\cache
 */
abstract class Base implements CacheInterface{

    // Memcache has a maximum cache lifetime of 30 days
    // 3 day
    const CACHE_CEILING = 604800;

    /**
     *
     */
    const DEFAULT_EXPIRE = 3600;

    /**
     * @var array
     */
    protected $config;

    /**
     * @var string
     */
    public static $defaultDriver = 'Memcached';

    /**
     * @var static[]
     */
    protected static $instances = array();

    /**
     * @var \Memcached
     */
    protected $driver;


    /**
     * @return mixed
     */
    abstract protected function getDriver();


    /**
     * @param null $driver
     * @param array $config
     * @return self
     */
    public static function instance($config = array(), $driver = null){
        // If there is no group supplied
        if ($driver === NULL){
            // Use the default setting
            $driver = self::$defaultDriver;
        }

        if (isset(self::$instances[$driver])){
            // Return the current group if initiated already
            return self::$instances[$driver];
        }

        // Create a new cache type instance
        $cache_class = __NAMESPACE__ . '\\' .  ucfirst($driver);
        self::$instances[$driver] = new $cache_class($config);

        // Return the instance
        return self::$instances[$driver];
    }


    /**
     * @param array $config
     */
    function __construct(array $config)  {
        $this->config = $config;
        if(!isset($this->config['default_expire'])){
            $this->config['default_expire'] = static::DEFAULT_EXPIRE;
        }

        if(!isset($this->config['prefix'])){
            $this->config['prefix'] = 'j';
        }

        $this->driver = $this->getDriver();
    }

    protected function sanitizeId($id) {
        return md5($this->config['prefix'] . $id);
    }

    public function setPrefix($prefix){
        $this->config['prefix'] = $prefix;
    }

    /**
     * Overload the __clone() method to prevent cloning
     *
     * @return  void
     * @throws  JException
     */
    public function __clone() {
        throw new JException('Cloning of J_Cache objects is forbidden');
    }

    /**
     * @param $id
     * @param null $default
     * @return mixed|null
     */
    public function get($id, $default = NULL){
        // Get the value from Memcache
        $value = $this->driver->get($this->sanitizeId($id));

        // If the value wasn't found, normalise it
        if ($value === FALSE) {
            $value = $default;
        }

        // Return the value
        return $value;
    }

    public function set($id, $data, $lifetime = 3600){
        // If lifetime is NULL
        if ($lifetime === NULL){
            // Set to the default expiry
            $lifetime = $this->config['default_expire'];
        }

        // If the lifetime is greater than the ceiling
        if ($lifetime > self::CACHE_CEILING) {
            // Set the lifetime to maximum cache time
            //$lifetime = self::CACHE_CEILING;
        } elseif ($lifetime < 0) {
            // Else if the lifetime is greater than zero
            $lifetime = 0;
        }

        // Set the data to memcache
        return $this->driver->set($this->sanitizeId($id), $data, $lifetime);
    }

    public function delete($id, $timeout = 0){
        return $this->driver->delete($this->sanitizeId($id), $timeout);
    }

    public function deleteAll(){
    }

    public function close(){
    }
}
// End JCache
