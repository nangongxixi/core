<?php

namespace j\tool;

/**
 * 掩码计算 (静态类static)
 *
 * 	用法事例 :
 *
 * 	$types = Mask::parse(24); 	// return: array(16,8)
 * 	$types = Mask::parse(15); 	// return: array(8,4,2,1)
 * 	$mask = Mask::add(4, 7);  	// return: 7 (因为7里包含了4)
 * 	$mask = Mask::add(2, 9);  	// return: 11
 * 	$mask = Mask::remove(2, 9); // return: 9 (因为9里不包含2)
 * 	$mask = Mask::remove(2, 7); // return: 5
 *
 */
class Mask{

    /**
     * 添加到掩码...
     *
     * @param int $value 要添加的数值
     * @param int $mask 源码值
     * @return int
     */
    static function add($value, $mask)	{
        if($mask & $value){
            return $mask;
        }

        return $mask + $value;
    }

    /**
     * 从掩码中移出
     *
     * @param int $value 要移出的数值
     * @param int $mask 源码值
     * @return int 移出数值后掩码
     */
    static function remove($value, $mask)	{
        if($mask & $value){
            return $mask - $value;
        }

        return $mask;
    }

    /**
     * 解析掩码
     *
     * @param int $mask 码值
     * @param string $returnType 返回类型
     * @return array or strin  or 0
     */
    static function parse($mask, $returnType = "array"){
        if((int)$mask <= 0){
            return $returnType == 'array' ? array() : 0;
        }

        // 解析掩码
        $values = array();
        $i = 0;
        while(($x = pow(2, $i++)) <= $mask){
            if($mask & $x){
                $values[] = $x;
            }
        }

        return $returnType == 'array' ? $values : implode(",", $values);
    }
}