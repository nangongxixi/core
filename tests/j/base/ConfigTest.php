<?php

use j\base\Config as Config;

/**
 * Class ConfigTest
 */
class ConfigTest extends PHPUnit_Framework_TestCase{

    function testConfig(){
        $config = Config::getInstance();
        $config->set('kernel.view', 'view');
        $this->assertEquals($config->get('kernel.view'), 'view');
        $this->assertEquals($config->get('kernel.view1', 'view'), 'view');

        $config->set([
            'jz' => [
                'passport1.login' => 'test',
                'passport' => [
                    'login' => 'test',
                    'login1' => 'test1',
                ],
            ],
            'base.view' => 1,
            'base.ns' => 2,
        ]);

        $this->assertEquals($config->get('jz.passport1.login'), 'test');
        $this->assertEquals($config->get('jz.passport1'), ['login' => 'test']);

        $this->assertEquals($config->get('base.view'), '1');
        $this->assertEquals($config->get('base'), [
            'view' => 1,
            'ns' => 2
        ]);

        $config->set(['jz.passport1.logout' => 'http://passport1.jz.x1.cn/?q=logout',]);
        $this->assertEquals($config->get('jz.passport1.logout'), 'http://passport1.jz.x1.cn/?q=logout');
    }


    function testLoadFile(){
        $config = Config::getInstance();
        $config->addAutoLoadDir([__DIR__ . '/config']);
	    $config->addAutoLoadNs('db');
        $config->set('db.conn', ['test' => true]);

        $db = $config->get('db.conn');
        $this->assertTrue(is_array($db));
        $this->assertTrue(isset($db['user']) && $db['user'] == 'root');

        $config->addAutoLoadNs('test');
        $config->set('test.key1', "value1");

        $test = $config->get('test');
        $this->assertTrue($test['key1'] == 'value1');
    }

    function testLoadFromYaconf(){
        $config = Config::getInstance();
        $config->setEnableYaconf(true);

        $db = $config->get('jz.redis');
        $this->assertTrue($db['host'] == 'localhost');
    }

    /**
     * @throws Exception
     */
    function testClear(){
        $key = 'kernel.view';
        $config = Config::getInstance();
        $config->set($key, 'view');
        $this->assertEquals($config->get($key), 'view');

        $config->clear($key);
        $this->assertEquals($config->has($key), false);
    }
}
