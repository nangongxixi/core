<?php

namespace j\security;

/**
 * The Encrypt library provides two-way encryption of text and binary strings
 * using rc4.
 * @see http://php.net/mcrypt
 *
 * $Id: Encrypt.php 4072 2009-03-13 17:20:38Z jheathco $
 *
 * @package    Core
 * @author     Kohana Team
 * @copyright  (c) 2007-2008 Kohana Team
 * @license    http://kohanaphp.com/license.html
 */
class Encrypt {
    protected $keys = "test";
    private $expiry = 1800;

    /**
     * @var self[]
     */
    private static $instance;

    /**
     * Encrypt constructor.
     * @param string $keys
     */
    public function __construct($keys = '') {
        if($keys){
            $this->setKeys($keys);
        }
    }

    /**
     * @param $string
     * @param string $operation
     * @param string $key
     * @param int $expiry
     * @param bool $forPassword
     * @return string
     */
    private function _authcode(
        $string, $operation = 'DECODE', $key = '', $expiry = 0,
        $forPassword = false
    ) {
        $ckey_length = 4;

        $expiry = $expiry ? $expiry : $this->expiry;
        
        //$key = md5($key ? $key : UC_KEY);
        $key = md5($key ? $key : $this->keys);
        $keya = md5(substr($key, 0, 16));
        $keyb = md5(substr($key, 16, 16));
        $keyc = $ckey_length
            ? ($operation == 'DECODE'
                ? substr($string, 0, $ckey_length)
                : substr(md5(microtime()), -$ckey_length))
            : '';

        $cryptkey = $keya.md5($keya.$keyc);
        $key_length = strlen($cryptkey);

        $string = $operation == 'DECODE' 
            ? base64_decode(substr($string, $ckey_length)) 
            : sprintf('%010d', $expiry ? $expiry + time() : 0).substr(md5($string.$keyb), 0, 16).$string;
        $string_length = strlen($string);

        $result = '';
        $box = range(0, 255);

        $rndkey = array();
        for($i = 0; $i <= 255; $i++) {
            $rndkey[$i] = ord($cryptkey[$i % $key_length]);
        }

        for($j = $i = 0; $i < 256; $i++) {
            $j = ($j + $box[$i] + $rndkey[$i]) % 256;
            $tmp = $box[$i];
            $box[$i] = $box[$j];
            $box[$j] = $tmp;
        }

        for($a = $j = $i = 0; $i < $string_length; $i++) {
            $a = ($a + 1) % 256;
            $j = ($j + $box[$a]) % 256;
            $tmp = $box[$a];
            $box[$a] = $box[$j];
            $box[$j] = $tmp;
            $result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
        }

        if($operation == 'DECODE') {
            if(
             ($forPassword || (substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0))
             && substr($result, 10, 16) == substr(md5(substr($result, 26).$keyb), 0, 16)) {
                return substr($result, 26);
            } else {
                return '';
            }
        } else {
            return $keyc.str_replace('=', '', base64_encode($result));
        }
    }

    /**
     * @param string $type
     * @return self
     */
    public static function instance($type = 'default'){
        // Create the singleton
        if(!isset(self::$instance[$type])){
            self::$instance[$type] = new static;
            if($type == 'url'){
                self::$instance[$type]->setKeys('testUrl');
                self::$instance[$type]->setExpiry(3600*24);
            }
        }
        
        return self::$instance[$type];
    }
    
    public function setKeys($key){
        $this->keys = $key;
    }
    
    public function encode($txt) {
        return $this->_authcode($txt, 'ENCODE');
    }
    
    public function decode($txt, $forPassword = false) {
        $txt = str_replace(' ', '+', $txt);
        return $this->_authcode($txt, 'DECODE', '', 0, $forPassword);
    }
    
    public function setExpiry($expire){
        $this->expiry = $expire;
    }
}

