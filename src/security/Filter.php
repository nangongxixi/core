<?php

namespace j\security;

/**
 * Class Filter
 * @package j\security
 */
class Filter{
    public static function safeHtml($html){
	    if(is_array($html)){
		    $html = array_map(array(__CLASS__, __METHOD__), $html);
	    } else {
		    $html = self::htmlFix($html);
		    $html = self::delBadTag($html);
            $html = str_replace([
                "src=",
                "id=",
                "class=",
                "\n"
            ], [
                " src=",
                " id=",
                " class=",
                ''
            ], $html);
	    }
	    return $html;
    }

    public static function htmlFix($html){
        if(!function_exists('tidy_repair_string'))
            return $html;

        return tidy_repair_string(
            $html,
            array(
                'output-xhtml' => true,
                'output-html'=>true,
                'show-body-only' => true,
                'literal-attributes' => true,
                'drop-font-tags' => true,
                'drop-proprietary-attributes' => true,
                'drop-font-tags' => true,
                'clean' => true,
            ),
            'utf8'
        );
    }

    public static function delBadTag($string){
        $string = preg_replace('/<a.+?>|<\/a>/is', '', $string);
        $string = preg_replace('/<script.+?<\/script\>/is', '', $string);
        $string = preg_replace('/<iframe.+?<\/iframe\>/is', '', $string);
        $string = preg_replace('/<object.+?<\/object\>/is', '', $string);
        return $string;
    }
}
