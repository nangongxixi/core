<?php

namespace j\event;

/**
 * Class EventResult
 * @package j\event
 */
class EventResult{
    /**
     * @var Event
     */
    protected $event;
    protected $result = [];

    /**
     * @param array $result
     */
    public function add($result) {
        $this->result[] = $result;
    }

    /**
     * @return array
     */
    public function getAll() {
        return $this->result;
    }

    /**
     * @return mixed
     */
    public function last() {
        if(count($this->result) > 0)
            return array_pop($this->result);
        return null;
    }

    public function setEvent($e){
        $this->event = $e;
    }

    /**
     * @return Event
     */
    public function getEvent(){
        return $this->event;
    }
}