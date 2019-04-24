<?php

namespace j\base;

/**
 * Trait ClassNameTrait
 * @package j\base
 */
trait ClassNameTrait {

    public static function className(){
        return get_called_class();
    }

}