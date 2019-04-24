<?php

namespace j\tool;

use j\security\Filter;
use j\called\CalledTrait;

/**
 * Class Modifier
 * @package j\tool
 */
class Modifier {

    use CalledTrait;

    /**
     * @param $value
     * @param $express
     * @return string
     * @throws \Exception
     */
    public function parse($value, $express) {
        $modifiers = explode("|", $express);
        foreach($modifiers as $modifier){
            $args = array();
            if(strpos($modifier, ":")){
                list($modifier, $args) = explode(":", $modifier, 2);
                $args = explode(":", $args);
            }

            switch($modifier) {
                case "date":
                    if(!is_numeric($value)) {
                        $value = strtotime($value);
                    }
                    $format = isset($args[0]) ? $args[0] : 'Y-m-d';
                    $value = date($format, $value);
                    break;

                case "string":
                    $value = $value . "";
                    break;

                case 'encode' :
                case 'clean' :
                    $value = Filter::safeHtml($value);
                    break;

                case "int" :
                    $value = intval($value);
                    break;

                case "len" :
                    if(!isset($args[1])){
                        $args[1] = 'utf8';
                    }
                    $value = mb_substr($value, 0, $args[0], $args[1]);
                    break;

                case 'default' :
                    if(!$value && isset($args[0])){
                        $value = implode(":", $args);
                    }
                    break;

                case 'txt' :
                    $value = strip_tags($value);
                    $value = str_replace(["\n", "\r", "&nbsp;", "\t"], "", $value);
                    break;

                default :
                    array_unshift($args, $value);
                    if(is_callable($modifier)) {
                        $value = call_user_func_array($modifier, $args);
                    } elseif($this->isCallable($modifier)) {
                        $value = $this->call($modifier, $args);
                    } else {
                        trigger_error("Invalid modifier({$modifier})");
                    }
            }
        }

        return $value;
    }
}
