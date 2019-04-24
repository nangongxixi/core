<?php

namespace j\net\http;

use j\log\TraitLog;

/**
 * Class Client
 * @package j\http
 */
class Client {

    use TraitLog;

    public $lastHead = [];
    public $timeout = 2;

	/**
	 * @var self
	 */
    private static $instance;

	/**
	 * @return Client
	 */
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * @param $url
     * @param array $param
     * @param array $options
     * @throws
     * @return mixed
     */
    function get($url, $param = [], $options = []){
//        if($autoReferer && !isset($set[CURLOPT_REFERER])){
//            $set[CURLOPT_REFERER] = 'http://www.baidu.com';
//            $set[CURLOPT_USERAGENT] = 'Baiduspider+(+http://www.baidu.com/search/spider.htm)';
//        }
        if($param){
            if(is_array($param)){
                $param = http_build_query($param);
            }
            $sp = !strpos($url, '?') ? '?' : '&';
            $url .= $sp . $param;
        }

        return $this->send($url, 'get', [], $options);
    }


    /**
     * @param $url
     * @param $method
     * @param array $param
     * @param array $options
     * @param bool $header
     * @return mixed
     * @throws \Exception
     */
    protected function send($url, $method, $param = [], $options = [], $header = false){
        $curl = curl_init();

        $this->initCurlOption($curl, $url, $param, $options);
        //curl_setopt($curl, CURLOPT_HTTPHEADER, array('Expect:'));

        $this->log($method . ':' . $url, 'debug');
        $this->log($param, 'debug');

        $response = curl_exec($curl);
        if($response === false){
            $errno = curl_errno($curl);
        }

        if($header){
            $this->lastHead = curl_getinfo($curl);
        }
        curl_close($curl);

        // error
        if(isset($errno)){
            $exception = new \Exception($this->errorMsg($errno, $url, $param));
            $exception->param = $param;
            $exception->method = $method;
            throw $exception;
        }

        /**
         * 默认结果中不包含header info,
         * 如果需要设置 CURLOPT_HEADER, 自行处理结果
         * curl_setopt($ch, CURLOPT_HEADER, 1);

        $bodyPos = strpos($response, "\r\n\r\n");
        if(is_numeric($bodyPos)){
        $response = substr($response, $bodyPos + 4);
        }
         */

        $this->log('response:' . $response, 'debug');
        return $response;
    }

	/**
	 * @param $url
	 * @param array $param
	 * @param array $options
	 * @return mixed
     * @throws
	 */
    function post($url, $param = [], $options = []){
        if(!isset($options[CURLOPT_POST])){
            // application/x-www-form-urlencoded
            $options[CURLOPT_POST] = 1;
        }

        return $this->send($url, 'post', $param, $options);
    }

	/**
	 * @param $ch
	 * @param $url
	 * @param $param
	 * @param $options
	 */
    protected function initCurlOption($ch, $url, $param, $options){
        // time out
        if(!isset($options[CURLOPT_TIMEOUT])){
            $options[CURLOPT_TIMEOUT] = $this->timeout;
        }

        // return result
        if(!isset($options[CURLOPT_RETURNTRANSFER])){
            $options[CURLOPT_RETURNTRANSFER] = 1;
        }

        $options[CURLOPT_URL] = $url;

        // https
        if(stripos($url,"https://") !== false){
            $options[CURLOPT_SSL_VERIFYPEER] = false;
            $options[CURLOPT_SSL_VERIFYHOST] = false;
        }

        // post fields
        if($param){
            if(is_array($param)){
                //  application/x-www-form-urlencoded
                $param = http_build_query($param);

                // 如果上传文件, 设置options:
                // CURLOPT_POST => 0,
                // CURLOPT_POSTFIELDS => $data
            }
            $options[CURLOPT_POSTFIELDS] = $param;
        }

        // http header
        if(isset($options['headers'])){
            $headers = $options['headers'];
            unset($options['headers']);
        } else {
            $headers = [];
        }

        foreach($options as $key => $value){
            if(is_numeric($key)){
                // other options
                curl_setopt($ch, $key, $value);
            } else {
                $headers[] = "{$key}: {$value}";
            }
        }

        if($headers){
            foreach($headers as $key => $value){
                if(!is_numeric($key)){
                    unset($headers[$key]);
                    $headers[] = "{$key}: {$value}";
                }
            }
            if(isset($options[CURLOPT_HTTPHEADER])){
                // 合并header
                $headers = array_merge($options[CURLOPT_HTTPHEADER], $headers);
            }

            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
    }

	/**
	 * @param $errno
	 * @param $url
	 * @param array $payload
	 * @return string
	 */
    protected function errorMsg($errno, $url, $payload = []){
        /**
         * cUrl error code reference can be found here:
         * http://curl.haxx.se/libcurl/c/libcurl-errors.html
         */
        switch ($errno) {
            case CURLE_UNSUPPORTED_PROTOCOL:
                $error = "Unsupported protocol ";
                break;
            case CURLE_FAILED_INIT:
                $error = "Internal cUrl error?";
                break;
            case CURLE_URL_MALFORMAT:
                $error = "Malformed URL [$url] -d " . json_encode($payload);
                break;
            case CURLE_COULDNT_RESOLVE_PROXY:
                $error = "Couldnt resolve proxy";
                break;
            case CURLE_COULDNT_RESOLVE_HOST:
                $error = "Couldnt resolve host";
                break;
            case CURLE_COULDNT_CONNECT:
                $error = "Could not connect to host";
                break;
            case CURLE_OPERATION_TIMEOUTED:
                $error = "Operation timed out on [$url]";
                break;
            default:
                $error = "Unknown error";
                if ($errno == 0)
                    $error .= ". Non-cUrl error";
                break;
        }
        return $error;
    }

    /**
     * @param $url
     * @param $method
     * @param array $payload
     * @return array|mixed
     * @throws
     */
    function requestJSON($url, $method, $payload = []) {
        $set = [
            CURLOPT_FORBID_REUSE => 0,
            CURLOPT_CUSTOMREQUEST => strtolower($method),
        ];

        if (is_array($payload) && count($payload) > 0) {
            $payload = json_encode($payload); // bug?
        }

        $response = $this->send($url, $method, $payload, $set, true);
        $data = json_decode($response, true);
        if (!$data) {
            $data = array(
                'error' => $response,
                "code" => $this->lastHead['http_code']
            );
        }

        return $data;
    }

	/**
	 * @param $requests
	 * @return array
	 */
    function multiRequest($requests){
        $conn = array();
        $mh = curl_multi_init();
        foreach($requests as $i => $row){
            $conn[$i] = curl_init();
            if(!isset($row['param'])){
                $row['param'] = [];
            }

            if(!isset($row['set'])){
                $row['set'] = [];
            }

            $this->initCurlOption($conn[$i], $row['url'], $row['param'], $row['set']);
            curl_multi_add_handle ($mh,$conn[$i]);
        }

        do {
            curl_multi_exec($mh, $running);
            curl_multi_select($mh);
        } while ($running > 0);

        $res = array();
        foreach ($requests as $i => $url) {
            $res[$i] = curl_multi_getcontent($conn[$i]);
            curl_close($conn[$i]);
        }

        return $res;
    }

	/**
	 * @param $urls
	 * @return array
	 */
    public static function curlMul($urls){
        $handle  = array(); $i = 0;
        $mh = curl_multi_init(); // multi curl handler
        foreach ($urls as $url) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_TIMEOUT, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1 );
            curl_multi_add_handle($mh, $ch); // 把 curl resource 放进 multi curl
            $handle[$i++] = $ch;
        }

        do {
            curl_multi_exec($mh, $running);
            curl_multi_select($mh);
        } while ($running > 0);

        $headInfo = $rs = $errors = [];
        foreach ($handle as  $k => $conn) {
            $err = curl_error ( $conn );
            if($errors){
                $errors[] = $err;
            } else {
                $headInfo[] = curl_getinfo ( $conn ); //返回头信息
                $rs[] = curl_multi_getcontent ( $conn ); //返回头信息
            }
            curl_close($conn);
            curl_multi_remove_handle($mh, $conn);
        }
        curl_multi_close($mh);
        return [$headInfo, $rs, $errors];
    }
}