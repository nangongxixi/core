<?php

namespace j\base;

use InvalidArgumentException;
use j\debug\Tracer;
use j\di\Container;
use j\di\ServiceProviderInterface;
use j\log\File;
use j\log\Log;

/**
 * Class ServiceProvider
 * @package j\debug
 */
class BaseProvider implements ServiceProviderInterface{

    /**
     * Registers services on the given container.
     *
     * This method should only be used to configure services and parameters.
     * It should not get services.
     *
     * @param Container $container A container instance
     */
    public function register($container) {
        $config = Config::getInstance();

        $container->sets([
            'log' => function() use($config){
                if($config->get('debug')){
                    $log = new Log();
                } else {
                    $log = new File($config->get('log.file', "/tmp/j.log"));
                }
                if($config->get('log.defer') && method_exists($log, 'setDefer')){
                    $log->setDefer();
                }

                if($level = $config->get('log.level') && method_exists($log, 'setMask')){
                    $log->setMask($level);
                }
                return $log;
            },
        ], false);

        if($config->get('tracer.enable')){
            $container->sets([
                'tracer' => function() use($config, $container){
                    $logger = $config->get('tracer.logger');
                    if($logger && is_string($logger)){
                        $logger = new File($logger);
                    } elseif(!$logger && $container->has('log')){
                        $logger = $container->get('log');
                    }

                    if(!is_object($logger)){
                        throw new InvalidArgumentException("tracer.logger");
                    }

                    $tracer = new Tracer($logger);
                    if($config->get('tracer.debugTrace')){
                        $tracer->enableDebugTrace = true;
                    }
                    return $tracer;
                },
            ], false);
        }

        $container->sets([
            'errorHandle' => [
                'class' => 'j\error\Handle',
                'arguments' => [
                    '@log'
                ]
            ],
        ], false);
    }
}