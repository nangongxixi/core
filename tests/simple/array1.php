<?php

$a1 = [
    'name' => 'product',
    'ui.prefix' => '/ui/',
    'ui.prefixes' => [
        'hplugs' => 'http://cdn.ui.jiuzheng.com/hplugs/',
        'hplugs1' => 'http://cdn.ui.jiuzheng.com/hplugs/'
    ],
    'url.uiHost' => 'http://cdn.ui.jiuzheng.com',
    'url.uiDevHost' => 'http://dev.ui.jz.cn',
    'url.hostPre' => 'http://vr.jiuzheng.com',
    'img.baseUrl' => '/images',
];

$a2 = [
    'name' => 'dev',
    'ui.prefixes' => [
        'hplugs' => 'http://dev.ui.jz.cn/hplugs/'
    ],
    'url.uiHost' => 'http://dev.ui.jz.cn',
    'url.hostPre' => 'http://vr.jz.cn',
];


$a = array_merge_recursive($a1, $a2);
$a1 = array_merge($a1, $a2);

var_dump($a);
var_dump($a1);