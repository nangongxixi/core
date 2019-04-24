<?php

namespace j\di;

use Closure;

/**
 * Interface ServiceLocatorInterface
 * @package j\di
 */
interface ServiceLocatorInterface {
    public function make($provider, $options = null);
    public function get($name, $options = null, $cache = true);
    public function set($name, $obj = null, $isFactory = false);
    public function has($name);
    public function extend($abstract, Closure $closure);
    public function register(ServiceProviderInterface $provider, array $values = array());
}