<?php
# j/too/Verifier.php
namespace j\tool;

use j\security\PasswordStrength;

/**
 * Class Verifier
 * @package j\tool
 */
class Verifier{

    static function float($value) {
        return preg_match('/^\d+(\.\d+)?$/', $value);
    }

    static function email($value){
        return preg_match('#^(_|\w)[a-zA-Z0-9_\-\.]+@[a-zA-Z0-9_\-\.]+\.[a-zA-Z]{2,4}$#', $value);
    }

    static function ip($value){
        return preg_match("/\A((([0-9]?[0-9])|(1[0-9]{2})|(2[0-4][0-9])|(25[0-5]))\.){3}(([0-9]?[0-9])|(1[0-9]{2})|(2[0-4][0-9])|(25[0-5]))\Z/", $value);
    }

    static function cn($value){
        return preg_match('#[\xa1-\xff]+#', $value);
    }

    static function en($value){
        return preg_match('#^[a-zA-Z]+$#', $value);
    }

    static function alnum($value){
        return ctype_alnum($value);
    }

    static function qq($value){
        return preg_match('#^[1-9]\d{4,10}$#', $value);
    }

    static function url($value){
        $rs =  preg_match('#^(https?://)?[_a-zA-Z0-9-]+(\.[_a-zA-Z0-9-_]+)+.*$#', $value);
        return $rs;
    }

    static function zip($value){
        return preg_match('#^\d{6}$#', $value);
    }

    static function between($value, $min, $max){
        return $value >= $min && $value <= $max;
    }

    static function ascii($value){
        $ar = null;
        $count = preg_match_all('/[\x20-\x7f]/', $value, $ar);
        return $count == strlen($value);
    }

    static function date($value){
        $test = @strtotime($value);
        return $test !== -1 && $test !== false;
    }

    static function mobile($phone) {
        if (preg_match("/^1[0-9]{10}$/", $phone)) {
            return true;
        }
        return false;
    }

    static function phone($value){
        //兼容格式: 国家代码(2到3位)-区号(2到3位)-电话号码(7到8位)-分机号(3位)
        if (preg_match("/^[\(\)\s-_\d]{7,}$/", $value) && preg_match('/\d/', $value)) {
            return true;
        }
        return false;
    }

    static function len($value, $minLen, $maxLen = 0) {
        $len = Strings::len($value);
        if($maxLen){
            return $len >= $minLen && $len <= $maxLen;
        }
        return $len >= $minLen;
    }

    static function max_length($value, $strLen){
        $len = Strings::len($value);
        if($len <= $strLen){
            return true;
        }
        return false;
    }

    static function min_length($value, $strLen, $mask = 0){
        if($mask & 1){
            $value = strip_tags($value, '<img>');
        }

        if($mask & 2){
            $value = str_replace('&nbsp;', '', $value);
            $value = str_replace(' ', '', $value);
            $value = str_replace('　', '', $value);
        }

        $len = Strings::len($value);
        if($strLen <= $len){
            return true;
        }
        return false;
    }

    static function exact_length($value, $strLen){
        $len = Strings::len($value);
        if($len == $strLen){
            return true;
        }
        return false;
    }

    static function id_card($value){
        return preg_match('/(^\d{15}$)|(^\d{18}$)|(^\d{17}(\d|X|x)$)/', $value);
    }

    static function password($password){
        return PasswordStrength::check($password);
    }

    static function badWords($value){
        $value = preg_replace('/[\s\-\_\*]/', '', $value);;
        $words = array(
            '老虎机', '刷单', '诈骗电话', '网络诈骗',
            '发票','办证','干扰器','赌博','电棒','电棍',
            '老虎机','针孔','成人用品',
            //'老虎机','针孔','透视','成人用品',
            '代考','追踪器', '跟踪器','气狗','作弊器',
            '氰化钠','氰化钾',' 氰化金钾','盐酸克伦特罗 盐酸氯胺酮',
            '盐酸莱克多巴胺','锑氧化汞','红色氧化汞',
            '氧化汞','铁氰化钾','甲卡西酮',' 甲基苯丙胺',
            '盐酸氯胺酮','锑氧化汞',
            '习进平','彭丽媛',
            '麦角乙二胺', '罂粟', 'LSD', 'Lysergids', '冰毒', '蝇毒'
        );
        foreach ($words as $word) {
            if(is_numeric(strpos($value, $word))){
                return $word;
            }
        }
        return true;
    }
}