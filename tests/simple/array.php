<?php

$arr = [
    'test' => [
        "abc"
    ]
];
sets(['jz.passport1.logout' =>  'http://passport1.jz.x1.cn/?q=logout'], $arr);
var_dump($arr);


//
//changeArr("key1", "value1", $arr['test']);
//changeArr("key2", "value2", $arr['test']);
//changeArr("jz.passport", "p1", $arr);
//changeArr("jz.passport1", "p2", $arr);
//changeArr("jz", [
//    'passport1.login' => 'a',
//    'passport1.login2' => 'b',
//] , $arr);
//var_dump($arr);
//
//$jz = getArr('jz', $arr);
//echo "jz:";
//var_dump($jz);
//
//$passport = getArr('jz.passport1', $arr);
//echo "jz.passport1:";
//var_dump($passport);
//
//echo "jz.passport1.login:";
//$p1 = getArr('jz.passport1.login', $arr);
//var_dump($p1);

function sets($config, & $attr){
    foreach($config as $k => $value){
        changeArr($k, $value, $attr);
    }
}

function changeArr($key, $value, & $parent){
    if(strpos($key, '.')){
        list($ns, $name) = explode('.', $key, 2);
        if(!isset($parent[$ns]) || !is_array($parent[$ns])){
            $parent[$ns] = array();
        }
        changeArr($name, $value, $parent[$ns]);
    } else {
        if(is_array($value)){
            if(!isset($parent[$key]) || !is_array($parent[$key])){
                $parent[$key] = array();
            }
            foreach($value as $_k => $_v){
                changeArr($_k, $_v, $parent[$key]);
            }
        } else {
            $parent[$key] = $value;
        }
    }
}

function getArr($key, $arr){
    $keys = explode(".", $key);
    $parent = $arr;
    while($key = array_shift($keys)){
        if(!isset($parent[$key])){
            return null;
        }
        if(!$keys){
            return $parent[$key];
        }
        $parent = $parent[$key];
    }
    return null;
}
