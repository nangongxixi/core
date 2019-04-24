<?php

namespace j\cache;

use Exception as JException;
use Memcached as Driver;

/**
 * Class Memcached
 * @package j\cache
 */
class Memcached extends Base {
    /**
     * @return Driver
     */
    protected function getDriver() {
        return new Driver();
    }

    /**
     * @param array $config
     * @throws JException
     */
    function __construct(array $config) {
        if ( ! extension_loaded('memcached')) {
            throw new JException('Memcache PHP extention not loaded');
        }

        parent::__construct($config);

        // Setup Memcache
        $this->driver->setOptions([
            Driver::OPT_CONNECT_TIMEOUT => 100,
            Driver::OPT_RECV_TIMEOUT => 100,
            ]);

        // Load servers from configuration
        if(!isset($this->config['servers'])){
            $servers = array(
                array(
                    'weight' => 1,
                    'host' => '127.0.0.1',
                    'port' => 11211,
                ));
        } else {
            $servers = $this->config['servers'];
            foreach($servers as $key => $conf){
                if(!isset($conf['weight'])){
                    $conf['weight'] =  1;
                }
                if(!isset($conf['port'])){
                    $conf['port'] =  11211;
                }

                $servers[$key] = [$conf['host'], $conf['port'], $conf['weight']];
            }
        }

        $this->driver->addServers($servers);
    }

    public function deleteAll() {
        return $this->driver->flush();
    }

    function close(){
        if($this->driver)
            $this->driver->quit();
    }
}
