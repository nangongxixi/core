<?php

/**
 *
 */

require_once __DIR__ . '/../autoload.php';

/**
 *
 */
function mulRequestTest(){
    try{
        $client = new j\net\http\Client();
        $requests = [
            ['url' => 'http://www.baidu.com/'],
            ['url' => 'http://www.baidu.com/'],
            ['url' => 'http://www.baidu.com/'],
            ['url' => 'http://www.baidu.com/'],
            ['url' => 'http://www.baidu.com/'],
            ['url' => 'http://www.baidu.com/'],
            ['url' => 'http://www.baidu.com/'],
            ['url' => 'http://www.bmlink.com/'],
            ['url' => 'http://www.sina.com/'],
            ['url' => 'http://www.163.com/'],
            ['url' => 'http://www.sohu.com/'],
        ];

        $timer = new j\debug\Timer(1);
        $result = $client->multiRequest($requests);
        echo count($result) . "\n";
        $timer->stop(1);

        $result2 = [];
        foreach($requests as $r){
            $result2[] = $client->get($r['url']);
        }
        echo count($result2) . "\n";
        $timer->stop(1);

    } catch (Exception $e){
        var_dump($e);
    }
}


function postArrayTest() {
    if(\j\net\http\Request::isPost()){
        echo "<pre>";
        var_dump($_GET);
        var_dump($_POST);
        return;
    }

    $data = [
        'args' => [
            'data' => [
                'A' => "test",
                'B' => 2
            ],
            'args2' => 3
        ],
        'init' => [
            1,
            2
        ]
    ];

    $data = http_build_query($data);
    $client = new j\net\http\Client();
    $url = "http://jzf.x1.cn/tests/j/http.php";
    $data = $client->post($url, $data);
    echo($data);
}

postArrayTest();