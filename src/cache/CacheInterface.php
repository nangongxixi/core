<?php
namespace j\cache;

/**
 * Interface CacheInterface
 * @package j\cache
 */
interface CacheInterface{

    public function get($id, $default = NULL);

    public function set($id, $data, $lifetime = 3600);

    public function delete($id, $timeout = 0);

    public function deleteAll();

}