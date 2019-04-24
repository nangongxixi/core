<?php

namespace j\security;

use j\error\Error;

/**
 * Class HashMd5
 * @package j\security
 */
class HashMd5{

    protected $key = "test";

    private static $instance;

	/**
	 * HashMd5 constructor.
	 * @param string $key
	 */
	public function __construct($key = ''){
		if($key){
			$this->setKey($key);
		}
	}

	/**
	 * @param string $key
	 */
	public function setKey($key){
		$this->key = $key;
	}

	/**
     * @return HashMd5
     */
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

	/**
	 * @param $params
	 * @param array $keys
	 * @param bool $urlencode
	 * @return mixed
	 */
    function genSign($params, $keys = [], $urlencode = true){
        $keys = $keys ?: array_keys($params);
        $params['nonce_str'] = $this->createNonceStr();
        $params['time'] = time();
        array_push($keys, 'nonce_str', 'time');
        $params['sign'] = $this->genSignString($params, $keys, $urlencode);

        return $params;
    }

	/**
	 * @param $attrs
	 * @param array $keys
	 * @param int $expire
	 * @return bool
	 */
    function checkSign($attrs, $keys = [], $expire = 0) {
        if(!isset($attrs['sign'])
            || !isset($attrs['time'])
            || !isset($attrs['nonce_str'])
        ){
            return false;
        }

        $signSource = $attrs['sign'];
        unset($attrs['sign']);
        unset($attrs['sign_type']);

        if(!$keys){
            $keys = array_keys($attrs);
        }

        if(!in_array('time', $keys)){
            array_push($keys, 'time');
        }

        if(!in_array('nonce_str', $keys)){
            array_push($keys, 'nonce_str');
        }

        $sign = $this->genSignString($attrs, $keys); //本地签名
        if ($signSource == $sign) {
            if(!$expire){
                return true;
            }

            if(isset($attrs['time']) && ($attrs['time'] + $expire > time())){
                return true;
            }

            Error::warning("expired for sign");
        }

        return false;
    }

    /**
     * 	作用：产生随机字符串，不长于32位
     */
    protected function createNonceStr( $length = 32 ){
        $chars = "abcdefghijklmno0123456789pqrstuvwxyz";
        $str ="";
        for ( $i = 0; $i < $length; $i++ )  {
            $str.= substr($chars, mt_rand(0, strlen($chars)-1), 1);
        }
        return $str;
    }

    protected function genSignString($params, $keys = [], $urlencode = true){
        if($keys){
            $tmpParams = array();
            foreach($keys as $key){
                $tmpParams[$key] = $params[$key];
            }
        } else {
            $tmpParams = $params;
        }

		//签名步骤一：按字典序排序参数
		ksort($tmpParams);
		$String = $this->formatBizQueryParaMap($tmpParams, $urlencode);
		//echo '【string1】'.$String.'</br>';

		//签名步骤二：在string后加入KEY
		$String = $String . "&key=" . $this->key;

		//echo "【string2】".$String."</br>";
		//签名步骤三：MD5加密
		$String = md5($String);
		//echo "【string3】 ".$String."</br>";

		//签名步骤四：所有字符转为大写
		$result = strtoupper($String);
		//echo "【result】 ".$result_."</br>";

		return $result;
    }

    protected function formatBizQueryParaMap($paraMap, $urlencode = false) {
        $buff = "";
        ksort($paraMap);
        foreach ($paraMap as $k => $v) {
            if($urlencode){
                $v = urlencode($v);
            }
            $buff .= $k . "=" . $v . "&";
        }

        $reqPar = '';
        if (strlen($buff) > 0){
            $reqPar = substr($buff, 0, strlen($buff)-1);
        }

        return $reqPar;
    }
}