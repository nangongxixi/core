<?php

namespace j\net\http;

use Exception;

/**
 * Class Request
 * @package j\http
 */
class Request{

    private $sUrl;

    /**
     * put your comment there...
     *
     * @var RequestHead
     */
    public $reqHeader;

    private $sResponse;

    /**
     * @param $url
     * @return Request
     * @throws Exception
     */
    public static function getResult($url){
        // first request
        $req = new Request($url);
        $req->sendRequest();

        $head = $req->getResponseHead();
        $code = $head->getCode();
        $cookies = $head->getCookies();
        while($code == 302 || $code == 301){
            $location = $head->getLocation();
            if(!$location){
                throw(new Exception('not found location'));
            }

            // next request
            $req = new Request($location);
            $req->reqHeader->setCookies($cookies);
            $req->sendRequest();

            $head = $req->getResponseHead();
            $code = $head->getCode();
            if($code == 200){
                return $req;
            }
            $cookies->append($head->getCookies());
        }

        return $req;
    }

    function __construct($sUrl) {
        $this->sUrl = $sUrl;
        $this->reqHeader = new RequestHead();
        $this->reqHeader->append('Accept:text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8');
        $this->reqHeader->append('Accept-Charset:GBK,utf-8;q=0.7,*;q=0.3');
        //$this->reqHeader->append('Accept-Encoding:gzip,deflate');
        $this->reqHeader->append('Accept-Language:zh-CN,zh;q=0.8');
        $this->reqHeader->append('DNT:1');
        $this->reqHeader->append('User-Agent: Mozilla/5.0 (Windows NT 5.1; rv:16.0) Gecko/20100101 Firefox/16.0');
    }

    function addRequestHeader($sHeader) {
        $this->reqHeader->append(trim($sHeader));
    }

    function sendRequest($sPostData = '') {
        // parse url
        $uri = $this->parse();
        $this->reqHeader->setPath($uri['path']);
        $this->reqHeader->append('Host: ' . strtolower($uri['host']));
        $this->reqHeader->append('Connection: Close');
        if ($sPostData){
            $this->reqHeader->setPostData($sPostData);
        }

        // create tcp connection
        // need host, port, head
        $sockHttp = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if (!$sockHttp){
            die('socket_create() failed!');
        }

        $host = gethostbyname($uri['host']);
        $port = $uri['port'];
        $resSockHttp = socket_connect($sockHttp, $host, $port);
        if (!$resSockHttp){
            die('socket_connect() failed!');
        }

        // write request head
        $sRequest = $this->reqHeader->__toString();
        socket_write($sockHttp, $sRequest, strlen($sRequest));

        // read response
        $this->sResponse = '';
        while ($sRead = socket_read($sockHttp, 4096)){
            $this->sResponse .= $sRead;
        }

        socket_close($sockHttp);
    }

    private function parse() {
        $arMatchUrlPart = array();
        $sPatternUrlPart = '/http:\/\/([a-z-\.0-9]+)(:(\d+)){0,1}(.*)/i';
        preg_match($sPatternUrlPart, $this->sUrl, $arMatchUrlPart);

        return array(
            'host' =>  $arMatchUrlPart[1],
            'port' =>  empty($arMatchUrlPart[3]) ? 80 : $arMatchUrlPart[3],
            'path' =>  empty($arMatchUrlPart[4]) ? '/' : $arMatchUrlPart[4],
        );
    }

    /**
     * put your comment there...
     *
     */
    function getResponse() {
        return $this->sResponse;
    }

    function getResponseBody() {
        return $this->sResponse;
//        $sPatternSeperate = '/\r\n\r\n/';
//        $arMatchResponsePart = preg_split($sPatternSeperate, $this->sResponse, 2);
//        return $arMatchResponsePart[1];
    }

    /**
     * put your comment there...
     * @return HttpResponseHead
     */
    function getResponseHead() {
        $sPatternSeperate = '/\r\n\r\n/';
        $arMatchResponsePart = preg_split($sPatternSeperate, $this->sResponse, 2);
        return new HttpResponseHead($arMatchResponsePart[0]);
    }
}

class RequestHead{
    private $map = array();
    private $method = 'GET';
    private $path = '';

    function append($row){
        $this->map[] = $row;
    }

    function setPostData($sPostData){
        $this->method = 'POST';
        $this->append("Content-Type: application/x-www-form-urlencoded");
        $this->append("Content-Length: " . strlen($sPostData));
        $this->append("");
        $this->append($sPostData);
    }

    function setPath($path){
        $this->path = $path;
    }

    function setCookies($cookies){
        $this->append('Cookie: ' . $cookies);
    }

    function __toString(){
        return
            $this->method . " " . $this->path . " HTTP/1.1\r\n" .
            implode("\r\n", $this->map) .
            "\r\n\r\n"
            ;
    }
}

class HttpResponseCookies{
    private $map = array();
    function add($key, $value = null){
        $this->map[$key] = $value;
    }

    function __toString(){
        $cookie = '';
        foreach ($this->map as $key => $value) {
            $cookie .= "{$key}={$value}; ";
        }
        return $cookie;
    }

    /**
     * put your comment there...
     *
     * @param HttpResponseCookies $cookies
     */
    function append($cookies){
        $items = $cookies->getCookies();
        foreach ($items as $key => $value) {
            $this->add($key, $value);
        }
    }

    function getCookies(){
        return $this->map;
    }
}

class HttpResponseHead{
    private $head;
    function __construct($head){
        $this->head = $head;
    }

    function getCode(){
        preg_match('#HTTP/1.1\s*(\d+)#i', $this->head, $m);
        return $m[1];
    }

    function getLocation(){
        preg_match('#Location: (.+?)[\r\n]#i', $this->head, $m);
        if($m){
            return $m[1];
        }
        return null;
    }

    function getCookies(){
        $cookies = new HttpResponseCookies();
        preg_match_all('/Set-Cookie: (.+?)=(.+?);/s', $this->head, $m);
        if($m){
            foreach ($m[1] as $i => $key) {
                $cookies->add($key, $m[2][$i]);
            }
        }
        return $cookies;
    }

    function __toString(){
        return $this->head;
    }
}

/**
 * @example
 *
$url[] = 'http://Product.ebdoor.com/Products/7372992.aspx';
foreach ($url as $_url) {
    $req = getRequest($_url);
    echo "<pre>";
    print_r($req);
    echo "</pre>";
    exit;
}
 */