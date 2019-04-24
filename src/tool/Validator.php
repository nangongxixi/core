<?php

namespace j\tool;

use Closure;
use Exception;
use j\error\Error;

/**
 * Validator
 * 
 */
class Validator {

    const EXCEPTION_CODE = 551;
    const REG = 1;
    const CALLBACK = 2;
    
    private $errors = array();
    
    private $_data;
    private $_rules = array();
    private $_requireFields = array();
    protected $errMsg =[
        'require' => '*',
        'default' => 'Filed(%s) invalid',
        'system' => 'Invalid data or ruler',
    ];
    
    private static $cSelfMap = array(
        'confirm' => '_confirm',
        );

	const RULE_REQUIRE = 'not_empty';
	const RULE_INT = 'int';
	const RULE_NUMBER = 'num';
	const RULE_FLOAT = 'float';
	const RULE_DATE = 'date';
	const RULE_ALNUM = 'alnum';
	const RULE_ASCII = 'ascii';
	const RULE_CN = 'cn';
	const RULE_EN = 'en';

	const RULE_BETWEEN = 'between';
	const RULE_MAX_LEN = 'max_length';
	const RULE_MIN_LEN = 'min_length';
	const RULE_LEN = 'len';

	const RULE_EMAIL = 'email';
	const RULE_IP = 'ip';
	const RULE_URL = 'url';
	const RULE_MOBILE = 'mobile';
	const RULE_ZIP = 'zip';
	const RULE_QQ = 'qq';
	const RULE_ID_CARD = 'id_card';
	const RULE_PHONE = 'phone';
	const RULE_PASSWORD = 'password';
	const RULE_BAD_WORDS = 'bad_words';
	const RULE_LEN_UFT8 = 'exact_length';

    private static $cMap = array(
        self::RULE_REQUIRE  => 'strlen',
        self::RULE_INT       => 'is_numeric',
        self::RULE_NUMBER    => 'is_numeric',
        self::RULE_FLOAT     => 'Verifier::float',
        self::RULE_IP        => 'Verifier::ip',
        self::RULE_EMAIL     => 'Verifier::email',
        self::RULE_CN        => 'Verifier::cn',
        self::RULE_EN        => 'Verifier::en',
        self::RULE_DATE      => 'Verifier::date',
        self::RULE_BETWEEN   => 'Verifier::between',
        self::RULE_ALNUM     => 'Verifier::alnum',
        self::RULE_ZIP       => 'Verifier::zip',
        self::RULE_QQ        => 'Verifier::qq',
	    self::RULE_URL       => 'Verifier::url',
        self::RULE_ASCII     => 'Verifier::ascii',
        self::RULE_LEN       => 'Verifier::len',
        self::RULE_MOBILE   => 'Verifier::mobile',
        self::RULE_MAX_LEN  => 'Verifier::max_length',
        self::RULE_MIN_LEN  => 'Verifier::min_length',
        self::RULE_LEN_UFT8 => 'Verifier::exact_length',
        self::RULE_ID_CARD  => 'Verifier::id_card',
        self::RULE_PHONE    => 'Verifier::phone',
        self::RULE_PASSWORD => 'Verifier::password',
        self::RULE_BAD_WORDS => 'Verifier::badWords',
        );
    private static $fixMap = false;
    
    /**
    * put your comment there...
    * 
    * @param mixed $data
    */
    public function __construct($data = array()){
        if(!self::$fixMap){
            self::$fixMap = true;
            foreach(self::$cMap as $key => $value){
                if(strpos($value, 'Verifier') === 0){
                    self::$cMap[$key] = __NAMESPACE__ . "\\" . $value;
                }
            }
        }
        $this->setData($data);
    }

    /**
     * @param $data
     * @return $this
     */
    function setData($data) {
        $this->_data = (array)$data;
        return $this;
    }

    /**
     * @param $value
     * @param $reField
     * @return bool
     */
    private function _confirm($value, $reField){
        return $value == $this->_data[$reField];
    }
    
    /**
     * @example
     *
     * $validate->rule('username', 'not_empty', 'username can not is empty');
     * $validate->rule('username', 'MyValidator::not_empty', 'username can not is empty');
     * $validate->rule('username', array($model, 'chkUsername'), 'username can not is empty');
     * $validate->rule('username', 'len[6,20]', 'username can not is empty');
     * $validate->rule('username', array('between', 1, 10), 'username can not is empty');
     *
     * @param $key
     * @param $rule
     * @param string $errMsg
     * @return $this
     * @throws \Exception
     */
    function rule($key, $rule, $errMsg = ''){
        
        if(is_array($rule) && !is_object($rule[0])){
            $args = array_slice($rule, 1);
            $rule = $rule[0];
        }elseif(is_string($rule) && $rule{0} != '/' && strpos($rule, '[')){
            $r = array();
            preg_match('/^(.+?)\[(.+?)\]/', $rule, $r);
            $rule = $r[1];
            $args = preg_split('/\s*,\s*/', $r[2], -1, PREG_SPLIT_NO_EMPTY);
        }else{
            $args = array();
        }

        if($rule instanceof Closure){
            $type = self::CALLBACK;
        }elseif(is_array($rule)){
            $type = self::CALLBACK;
            $args = array_slice($rule, 2);
            $rule = array($rule[0], $rule[1]);
        }elseif(is_string($rule)){
            if(in_array($rule{0}, array('/', '#'), true)){
                $type = self::REG;
            }elseif(isset(self::$cSelfMap[$rule])){
                $type = self::CALLBACK;
                $rule = array($this, self::$cSelfMap[$rule]);
            }else{
                if(isset(self::$cMap[$rule])){
                    $rule = self::$cMap[$rule];
                }
                
                if(function_exists($rule)){
                    $type = self::CALLBACK; 
                }elseif(strpos($rule, '::')){
                    $rule = explode('::', $rule);
                    $type = self::CALLBACK;
                }else{
                    throw(new Exception("RULE FORMAT ERROR({$rule})"));
                }
            }
        }else{
            throw(new Exception("RULE FORMAT ERROR({$rule})"));
        }
        
        $this->_rules[$key][] = array(
            'express' => $rule,
            'type' => $type,
            'msg' => $errMsg,
            'args' => $args
            );
        return $this;
    }
    
        
    /**
     * @param array $rules array(field, rule, [error message])
     * @return $this
     * @throws \Exception
     */
    function rules($rules){
        if(!is_array($rules)){
            return $this;
        }
        
        foreach ($rules as $k => $rule){
            if(!is_array($rule)){
                throw(new Exception("Rule must be a array(field, rule, [error message])"));
            }
            if(is_numeric($k)){
                call_user_func_array(array($this, 'rule') , $rule);
            }else{
                if(is_array(current($rule))){
                    foreach ($rule as $_r) {
                        array_unshift($_r, $k);
                        call_user_func_array(array($this, 'rule') , $_r);
                    }
                }else{
                    array_unshift($rule, $k);
                    call_user_func_array(array($this, 'rule') , $rule);
                }
            }
        }
        return $this;
    }

    /**
     * @param null $f
     * @return $this
     */
    function ruleClear($f = null) {
        if($f){
            $this->_rules[$f] = array();
        }else{
            $this->_rules = array();
        }
        return $this;
    }
    
    /**
    * put your comment there...
    * 
    * // example
    * $validate->requireFields('a', 'b', 'c')
    * @return Validator
    */
    function requireFields() {
        $fields = func_get_args();
        if(count($fields) == 1){
            $fields = (array)$fields[0];
        }
        $this->_requireFields = $fields;
        return $this;
    }
    
    /**
    * put your comment there...
    * @return Validator
    */
    function requireClear() {
        $this->_requireFields = array();
        return $this;
    }
    
    /**
    * put your comment there...
    * @return Validator
    */
    function reset() {
        $this->errors = array();
        $this->_rules = array();
        $this->_requireFields = array();
        return $this;
    }
    
    /**
    * put your comment there...
    * 
    * @param mixed $full
    * @return boolean
    */
    function check($full = true){
        $data = $this->_data;
        
        if(!is_array($data) || !is_array($this->_rules)){
            $this->setError('system', $this->errMsg['system']);
            return false;
        }

        foreach ($data as $f => $value) {
            if(!$value && !is_numeric($value)){
                if(in_array($f, $this->_requireFields)){
                    $this->setError($f, $this->errMsg['require']);
                }
                continue;
            }
            
            if(!isset($this->_rules[$f])){
                continue;
            }
            
            foreach ($this->_rules[$f] as $r) {
                $match = false;
                switch ($r['type']) {
                    case self::REG :
                        $match = preg_match($r['express'], $value);
                        break;
                    case self::CALLBACK :
                        $args = $r['args'];
                        array_unshift($args, $value);
                        $match = call_user_func_array($r['express'], $args);
                }

                if($match === true || (is_numeric($match) && $match > 0)){
                    continue;
                }

                if(isset($r['msg'])){
                    $msg = $r['msg'];
                }else{
                    $msg = sprintf($this->errMsg['default'], $f);
                }
                if(is_string($match)){
                    $msg .= " {$match}";
                }

                $this->setError($f, $msg);
                break;
            }
        }
        
        // if require then check data[$key] exist
        // default require any key
        if($full){
            foreach ($this->_requireFields as $f) {
                if(!isset($data[$f]) || (is_string($data[$f]) && Strings::len($data[$f]) == 0)){
                    $this->setError($f, $this->errMsg['require']);
                }
            }
        }

        if($this->errors) {
            Error::validError($this->errors);
            return false;
        }
        
        return true;
    }

    protected function setError($k, $msg){
        $this->errors[$k] = $msg;
    }

    function getErrors(){
        return $this->errors;
    }
}// end class
