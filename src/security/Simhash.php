<?php

namespace j\security;

/**
 * Class Simhash
 * 字符串相似度
 * @package j\security
 */
class Simhash {

    public $m_hash = null;
    public $hashBits = null;

    //构造函数
    public function __construct($tokens = array(), $hashbits = 64) {
        $this->m_hashbits = $hashbits;
        $this->m_hash = $this->simhash($tokens);
    }

    public function getMhash() {
        return doubleval($this->m_hash);
    }

    //to string
    public function __toString() {
        return strval($this->m_hash) . '';
    }

    //返回hash值
    public function simhash($tokens) {
        if (!is_array($tokens)) {
            $tokens = array($tokens);
        }

        $v = array_fill(0, $this->m_hashbits, 0);
        foreach ($tokens as $x) {
            $x = $this->stringHash($x);
            for ($i = 0; $i < $this->m_hashbits; $i++) {
                $bitmask = gmp_init(1);
                gmp_setbit($bitmask, $i);
                $bitmask = gmp_sub($bitmask, 1);
                if (gmp_strval(gmp_and($x, $bitmask)) != "0") {
                    $v[$i] += 1;
                } else {
                    $v[$i] -= 1;
                }
            }
        }
        $sum = 0;
        for ($i = 0; $i < $this->m_hashbits; $i++) {
            if ($v[$i] >= 0) {
                $num = gmp_init(1);
                gmp_setbit($num, $i);
                $num = gmp_sub($num, 1);
                $sum = gmp_add($sum, $num);
            }
        }
        return gmp_strval($sum);
    }

    //求海明距离
    public function hammingDistance($other) {
        $a = gmp_init($this->m_hash);
        $b = gmp_init($other->m_hash);

        $c = gmp_init(1);
        gmp_setbit($c, $this->m_hashbits);
        $c = gmp_sub($c, 2);
        $x = gmp_and(gmp_xor($a, $b), $c);
        $tot = 0;
        while (gmp_strval($x)) {
            $tot += 1;
            $x = gmp_and($x, gmp_sub($x, 1));
        }
        return $tot;
    }

    //求相似度
    public function similarity($other) {
        $a = floatval($this->m_hash);
        $b = floatval($other->m_hash);
        if ($a > $b) {
            return $b / $a;
        } else {
            return $a / $b;
        }
    }

    protected function stringHash($source) {
        if (empty($source)) {
            return 0;
        } else {
            $x = ord($source[0]) << 7;

            $m = 1000003;
            $mask = gmp_sub(gmp_pow("2", $this->m_hashbits), 1);
            $len = strlen($source);

            for ($i = 0; $i < $len; $i++) {
                $x = gmp_and(gmp_xor(gmp_mul($x, $m), ord($source[$i])), $mask);
            }
            $x = gmp_xor($x, $len);
            if (intval(gmp_strval($x)) == -1) {
                $x = -2;
            }
            return $x;
        }
    }

    /**
     * @param null $input
     * @return null|string
     */
    function bstr2bin($input = null)  { // Binary representation of a binary-string
        if (!$input && $this->m_hash) {
            $input = $this->getMhash();
        }

        if (!is_string($input))
            return null;

        // Sanity check
        // Unpack as a hexadecimal string
        $value = unpack('H*', $input);
        // Output binary representation
        $value = str_split($value[1], 1);
        $bin = '';

        foreach ($value as $v) {
            $b = str_pad(base_convert($v, 16, 2), 4, '0', STR_PAD_LEFT);
            $bin .= $b;
        }

        return $bin;
    }

    /**
     * @param int $jinzhi 进制位
     * @param null $shu
     * @param int $w
     * @return string
     */
    function binary($jinzhi = 2, $shu = null, $w = 32) {
        if (!$shu && $this->m_hash) {
            $shu = $this->getMhash();
        }

        $zifu = "";
        while ($shu != 0) {
            $linshi = bcmod($shu, $jinzhi);
            switch ($jinzhi) {
                case 2:
                    $zifu = decbin($shu);
                    return $zifu;
                case 8:
                    $zifu = decoct($shu);
                    return $zifu;
                case 16:
                    $zifu = dechex($shu);
                    return $zifu;
                case 36:
                    if ($linshi >= 10) {
                        $zifu .= chr(($linshi + 55));
                    } else {
                        $zifu .= $linshi;
                    }
                    break;
                case 62:
                    if (($linshi >= 10) && ($linshi < 36)) {
                        $zifu .= chr($linshi + 55);
                        break;
                    }

                    if (($linshi >= 36) && ($linshi < 62)) {
                        $zifu .= chr($linshi + 61);
                        break;
                    }

                    $zifu .= $linshi;
                    break;
                default:
                    $zifu .= $linshi;
                    break;
            }
            $shu = intval(bcdiv($shu, $jinzhi));
        }
        for ($i = strlen($zifu); $i < $w; $i++) {
            $zifu .= "0";
        }
        return strrev($zifu);
    }
}