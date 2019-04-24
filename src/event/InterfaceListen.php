<?php

namespace j\event;

/**
 * Interface InterfaceListen
 * @package j\event
 */
interface InterfaceListen{
    /**
     * @param TraitManager $em
     */
    public function bind($em);
}
