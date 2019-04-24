<?php

namespace j\security;

/**
 * Class PasswordStrength
 * ^(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,10}$
 * @package j\security
 */
class PasswordStrength {

    static function check($password){
        if(self::isWeak($password)){
            return false;
        }

        if(self::strength($password) < 50){
            return false;
        }

        if(self::points($password) < 2){
            return false;
        }

        return true;
    }

    /**
     * @param $string
     * @return float|int
     */
    static function strength($string) {
        $h = 0;
        $size = strlen($string);
        //print_r(count_chars($string, 1));
        foreach (count_chars($string, 1) as $v) {   //count_chars：返回字符串所用字符的信息
            $p = $v / $size;
            $h -= $p * log($p) / log(2);
        }
        $strength = ($h / 4) * 100;
        if ($strength > 100) {
            $strength = 100;
        }
        return $strength;
    }

    static function points($str){
        $expresses  = [
            "/[0-9]+/",
            "/[0-9]{3,}/",
            "/[a-z]+/",
            "/[a-z]{3,}/",
            "/[A-Z]+/",
            "/[A-Z]{3,}/",
            ["/[_|\-|+|=|*|!|@|#|$|%|^|&|(|)]+/", 2],
            "/[_|\-|+|=|*|!|@|#|$|%|^|&|(|)]{3,}/",
            function($str){
                return strlen($str) >= 10;
            }
        ];

        $score = 0;
        foreach($expresses as $exp){
            $points = 1;
            if($exp instanceof \Closure){
                $f = $exp($str);
            } elseif(is_array($exp)){
                $points = $exp[1];
                $f = preg_match($exp[0], $str);
            } else {
                $f = preg_match($exp, $str);
            }
            if($f)
                $score += $points;
        }

        return $score;
    }

    /**
     * @param $password
     * @return bool
     */
    static function isWeak($password) {
        $weakDic = [
            '000000', '111111', '11111111', '112233',  '123123',
            '123321', '123456', '12345678',  '654321',  '666666',
            '888888', 'abcdef', 'abcabc',   'abc123', 'a1b2c3',
            'aaa111', '123qwe', 'qwerty', 'qweasd', 'admin',
            'password', 'p@ssword', 'passwd','iloveyou','5201314',
        ];
        return in_array($password, $weakDic);
    }
}