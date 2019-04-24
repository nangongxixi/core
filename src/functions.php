<?php

use j\base\Config;
use j\debug\Tracer;
use j\di\Container;

if (!function_exists('gav')) {
    /**
     * 1. Get Array item with key
     * @param $arr
     * @param $key
     * @param null $def
     * @return mixed|null
     */
    function gav($arr, $key, $def = null)
    {
        if (is_array($arr)) {
            return array_key_exists($key, $arr) ? $arr[$key] : $def;
        } else {
            if ($arr instanceof ArrayAccess) {
                return $arr->offsetExists($key) ? $arr[$key] : $def;
            } else {
                //throw(new Exception('arg 1 is not a array'));
                trigger_error('arg 1 is not a array');
                return null;
            }
        }
    }
}

if (!function_exists('service')) {
    /**
     * 2. Get the available container instance.
     *
     * @param string $make
     * @param array $parameters
     * @param bool $cache
     * @return mixed|Container
     * @throws
     */
    function service($make = null, $parameters = [], $cache = false)
    {
        if (is_null($make)) {
            return Container::getInstance();
        }

        static $caches = [];
        if ($cache && is_string($parameters)) {
            $id = $cache . '-' . $parameters;
            if (!isset($caches[$id])) {
                $caches[$id] = Container::getInstance()->make($make, $parameters);
            }
            return $caches[$id];
        } else {
            return Container::getInstance()->make($make, $parameters);
        }
    }
}

if (!function_exists('config')) {

    /**
     * 3. Get / set the specified configuration value.
     *
     * If an array is passed as the key, we will assume you want to set an array of values.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed|\j\base\Config
     */
    function config($key = null, $default = null)
    {
        $config = Config::getInstance();
        if (is_null($key)) {
            return $config;
        }

        if (is_array($key)) {
            return $config->set($key);
        }

        return $config->get($key, $default);
    }
}

if (!function_exists('trace')) {
    /**
     * 4. debug var
     * @param $var
     * @param string $title
     * @param string $group
     * @param int $startTrace
     */
    function trace($var, $group = 'info', $title = 'Dump var', $startTrace = 2)
    {
        if (!service()->has('tracer')) {
            return;
        }

        /** @var Tracer $tracer */
        static $tracer;
        if (!isset($tracer)) {
            $tracer = service('tracer');
            $tracer->setEnable(true);
        }

        $tracer->trace($var, $group, [
            'title' => $title,
        ], $startTrace);
    }
}
