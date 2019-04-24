<?php

namespace j\net\base;

use j\log\TraitLog;

/**
 * Class Protocol
 * @package j\net\base
 */
abstract class Protocol implements InterfaceProtocol{

    use TraitLog;

    /**
     * @var Server
     */
    public $server;

    /**
     * @var array
     */
    protected $clients;
}