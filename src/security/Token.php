<?php

namespace j\security;

/**
 * Token Handler
 *
 * @package  security
 * @author   Frederic Guillot
 */
class Token
{
    /**
     * Generate a random token with different methods: openssl or /dev/urandom or fallback to uniqid()
     *
     * @static
     * @access public
     * @return string  Random token
     * @throws
     */
    public static function getToken()
    {
        if (function_exists('random_bytes')) {
            return bin2hex(random_bytes(30));
        } elseif (function_exists('openssl_random_pseudo_bytes')) {
            return bin2hex(openssl_random_pseudo_bytes(30));
        } elseif (ini_get('open_basedir') === '' && strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
            return hash('sha256', file_get_contents('/dev/urandom', false, null, 0, 30));
        }

        return hash('sha256', uniqid(mt_rand(), true));
    }
}
