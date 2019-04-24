<?php

namespace j\cache;

use Exception;
use Memcache as Driver;

/**
 * Class Memcache
 * @package j\cache
 */
class Memcache extends Base {

    /**
     * @var Driver
     */
    protected $driver;

    /**
     * @return Driver
     */
    protected function getDriver() {
        return new Driver();
    }

    /**
     * Flags to use when storing values
     *
     * @var string
     */
    protected $flags;


    /**
     *
     * @param   array $config
     * @throws  Exception
     */
    function __construct(array $config) {
        // Check for the memcache extention
        if ( ! extension_loaded('memcache')) {
            throw new Exception('Memcache PHP extention not loaded');
        }

        parent::__construct($config);


        // Load servers from configuration
        if(!isset($this->config['servers'])){
            $servers = array(
                array(
                    'persistent' => false,
                    'host' => '127.0.0.1',
                    'port' => 11211,
                )
            );
        } else {
            $servers = $this->config['servers'];
        }

        // Setup default server configuration
        $defConfig = array(
            'host'             => 'localhost',
            'port'             => 11211,
            'persistent'       => FALSE,
            'weight'           => 1,
            'timeout'          => 1,
            'retry_interval'   => 15,
            'status'           => TRUE,
            'failure_callback' => NULL,
        );

        // Add the memcache servers to the pool
        foreach ($servers as $server) {
            // Merge the defined config with defaults
            $server += $defConfig;
            $cache = new Driver;
            if(!$cache->connect($server['host'], $server['port'], 0.3)){
                continue;
            }

            if ( ! $this->driver->addServer(
                $server['host'], $server['port'], $server['persistent'],
                $server['weight'], $server['timeout'],
                $server['retry_interval'], $server['status'],
                $server['failure_callback'])
            ){
                throw new Exception('Memcache could not connect to host');
            }
        }

        // Setup the flags
        if(isset($this->config['compression']) && $this->config['compression']){
            $this->flags = MEMCACHE_COMPRESSED;
        }
    }


    public function set($id, $data, $lifetime = NULL){
        // If lifetime is NULL
        if ($lifetime === NULL){
            // Set to the default expiry
            $lifetime = $this->config['default_expire'];
        }

        // If the lifetime is greater than the ceiling
        if ($lifetime > self::CACHE_CEILING) {
            // Set the lifetime to maximum cache time
            $lifetime = self::CACHE_CEILING;
        }
        // Else if the lifetime is greater than zero
        elseif ($lifetime < 0){
            $lifetime = 0;
        }

        // Set the data to memcache
        return $this->driver->set($this->sanitizeId($id), $data, $this->flags, $lifetime);
    }


    public function deleteAll() {
        $result = $this->driver->flush();

        // We must sleep after flushing, or overwriting will not work!
        // @see http://php.net/manual/en/function.memcache-flush.php#81420
        sleep(1);

        return $result;
    }

    function close(){
        if($this->driver)
            $this->driver->close();
    }
}