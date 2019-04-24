<?php

namespace j\error;

/**
 * Class InfoNotFound
 * @package j\mvc\exception
 */
class Exception extends \Exception {
    /**
     * @var
     */
    protected $info;

    /**
     * @var int
     */
    protected $code = 502;

    /**
     * @var int
     */
    protected $level = E_WARNING;

    /**
     * @param mixed $info
     */
    public function setInfo($info) {
        $this->info = $info;
    }

    /**
     * @return mixed
     */
    public function getInfo() {
        return $this->info;
    }

    /**
     * @return int
     */
    public function getLevel() {
        return $this->level;
    }

    /**
     * @return bool|mixed
     */
    public function getLevelName(){
        return Error::translateLevel($this->level);
    }

    /**
     * @param int $level
     */
    public function setLevel($level) {
        $this->level = $level;
    }
}