<?php

namespace j\log;

/**
 * Class File
 * @package j\log
 */
class File extends Log {

    protected $file;
    protected $maxSize = 1000000;

    function __construct($file = '/tmp/php-server-log.txt') {
        parent::__construct();
        $this->file = $file;
    }

    /**
     * @param string $file
     */
    public function setFile($file) {
        $this->file = $file;
    }

	public function dispose($record, $real = false){
        if(!$real && $this->defer){
            $this->stack[] = $record;
        } else {
            file_put_contents(
                $this->getFile($record['level']),
                $this->format($record),
                FILE_APPEND
            );
        }
	}

    function flush() {
	    $content = [];
        foreach($this->stack as $record){
            $content[] = $this->format($record) ;
        }
        file_put_contents($this->file, implode("\n", $content), FILE_APPEND);
        $this->stack = [];
    }

    protected function getFile($type){
        return $this->file;
    }

    /**
     * @return bool|int
     */
    public function logrotate(){
        if(file_exists($this->file)){
            $size = filesize($this->file);
            if($size > $this->maxSize){
                return file_put_contents($this->file, '');
            }
        }
        return true;
    }
}