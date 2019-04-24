<?php

namespace j\net\http;

/**
 * Class Url
 * @package j\http
 */
class Url {
    /**
     * 是否相对路径
     */
    public static function isRelative($url) {
        return strncmp($url, '//', 2) && strpos($url, '://') === false;
    }

    /**
     * @param $url
     * @param string|array $params
     * @return string
     */
    public static function addParams($url, $params){
        if(is_array($params)){
            $params = http_build_query($params);
        }
        return $url . (strpos($url, '?') ===  false ? '?' : '&') . $params;
    }
}