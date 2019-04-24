<?php

namespace j\cache;

use Yac as Driver;

/**
 * Class Yac
 * @package j\cache
 */
class Yac extends Base {

    protected function getDriver() {
        return new Driver($this->config['prefix']);
    }


    public function deleteAll() {
        $this->driver->flush();
    }
}