<?php

namespace j\tool;

/**
 * String handling class for utf-8 data
 * Wraps the phputf8 library
 * All functions assume the validity of utf-8 strings.
 *
 * @static
 * @package        j.Framework
 * @subpackage    Utilities
 * @since        1.5
 */
abstract class Strings {

    /**
     * @param $string
     * @return int
     */
    public static function len($string){
        return mb_strlen($string, 'utf-8');
    }

    /**
     * Returns the number of bytes in the given string.
     * This method ensures the string is treated as a byte array by using `mb_strlen()`.
     * @param string $string the string being measured for length
     * @return integer the number of bytes in the given string.
     */
    public static function byteLength($string){
        return mb_strlen($string, '8bit');
    }

    /**
     * Returns the portion of string specified by the start and length parameters.
     * This method ensures the string is treated as a byte array by using `mb_substr()`.
     * @param string $string the input string. Must be one character or longer.
     * @param integer $start the starting position
     * @param integer $length the desired portion length. If not specified or `null`, there will be
     * no limit on length i.e. the output will be until the end of the string.
     * @return string the extracted part of string, or FALSE on failure or an empty string.
     * @see http://www.php.net/manual/en/function.substr.php
     */
    public static function byteSubstr($string, $start, $length = null) {
        return mb_substr($string, $start, $length === null ? mb_strlen($string, '8bit') : $length, '8bit');
    }

    /**
     * Tests a string as to whether it's valid UTF-8 and supported by the
     * Unicode standard
     * Note: this function has been modified to simple return true or false
     * @author <hsivonen@iki.fi>
     * @param string UTF-8 encoded string
     * @return boolean true if valid
     * @since 1.6
     * @see http://hsivonen.iki.fi/php-utf8/
     * @see compliant
     */
    public static function valid($str){
        $mState = 0;    // cached expected number of octets after the current octet
                        // until the beginning of the next UTF8 character sequence
        $mUcs4  = 0;    // cached Unicode character
        $mBytes = 1;    // cached expected number of octets in the current sequence

        $len = strlen($str);

        for ($i = 0; $i < $len; $i++){
            $in = ord($str{$i});

            if ($mState == 0){
                // When mState is zero we expect either a US-ASCII character or a
                // multi-octet sequence.
                if (0 == (0x80 & ($in))) {
                    // US-ASCII, pass straight through.
                    $mBytes = 1;
                } else if (0xC0 == (0xE0 & ($in))) {
                    // First octet of 2 octet sequence
                    $mUcs4 = ($in);
                    $mUcs4 = ($mUcs4 & 0x1F) << 6;
                    $mState = 1;
                    $mBytes = 2;
                } else if (0xE0 == (0xF0 & ($in))) {
                    // First octet of 3 octet sequence
                    $mUcs4 = ($in);
                    $mUcs4 = ($mUcs4 & 0x0F) << 12;
                    $mState = 2;
                    $mBytes = 3;
                } else if (0xF0 == (0xF8 & ($in))) {
                    // First octet of 4 octet sequence
                    $mUcs4 = ($in);
                    $mUcs4 = ($mUcs4 & 0x07) << 18;
                    $mState = 3;
                    $mBytes = 4;
                } else if (0xF8 == (0xFC & ($in))) {
                    /* First octet of 5 octet sequence.
                     *
                     * This is illegal because the encoded codepoint must be either
                     * (a) not the shortest form or
                     * (b) outside the Unicode range of 0-0x10FFFF.
                     * Rather than trying to resynchronize, we will carry on until the end
                     * of the sequence and let the later error handling code catch it.
                     */
                    $mUcs4 = ($in);
                    $mUcs4 = ($mUcs4 & 0x03) << 24;
                    $mState = 4;
                    $mBytes = 5;
                } else if (0xFC == (0xFE & ($in))) {
                    // First octet of 6 octet sequence, see comments for 5 octet sequence.
                    $mUcs4 = ($in);
                    $mUcs4 = ($mUcs4 & 1) << 30;
                    $mState = 5;
                    $mBytes = 6;

                } else {
                    /* Current octet is neither in the US-ASCII range nor a legal first
                     * octet of a multi-octet sequence.
                     */
                    return FALSE;
                }
            }
            else
            {
                // When mState is non-zero, we expect a continuation of the multi-octet
                // sequence
                if (0x80 == (0xC0 & ($in)))
                {
                    // Legal continuation.
                    $shift = ($mState - 1) * 6;
                    $tmp = $in;
                    $tmp = ($tmp & 0x0000003F) << $shift;
                    $mUcs4 |= $tmp;

                    /**
                     * End of the multi-octet sequence. mUcs4 now contains the final
                     * Unicode codepoint to be output
                     */
                    if (0 == --$mState)
                    {
                        /*
                         * Check for illegal sequences and codepoints.
                         */
                        // From Unicode 3.1, non-shortest form is illegal
                        if (((2 == $mBytes) && ($mUcs4 < 0x0080)) ||
                            ((3 == $mBytes) && ($mUcs4 < 0x0800)) ||
                            ((4 == $mBytes) && ($mUcs4 < 0x10000)) ||
                            (4 < $mBytes) ||
                            // From Unicode 3.2, surrogate characters are illegal
                            (($mUcs4 & 0xFFFFF800) == 0xD800) ||
                            // Codepoints outside the Unicode range are illegal
                            ($mUcs4 > 0x10FFFF)) {
                                return FALSE;
                            }

                        // Initialize UTF8 cache.
                        $mState = 0;
                        $mUcs4  = 0;
                        $mBytes = 1;
                    }
                }
                else
                {
                    /**
                     *((0xC0 & (*in) != 0x80) && (mState != 0))
                     * Incomplete multi-octet sequence.
                     */
                    return FALSE;
                }
            }
        }
        return TRUE;
    }

    /**
     * Tests whether a string complies as UTF-8. This will be much
     * faster than utf8_is_valid but will pass five and six octet
     * UTF-8 sequences, which are not supported by Unicode and
     * so cannot be displayed correctly in a browser. In other words
     * it is not as strict as utf8_is_valid but it's faster. If you use
     * is to validate suser input, you place yourself at the risk that
     * attackers will be able to inject 5 and 6 byte sequences (which
     * may or may not be a significant risk, depending on what you are
     * are doing)
     * @see valid
     * @see http://www.php.net/manual/en/reference.pcre.pattern.modifiers.php#54805
     * @param string UTF-8 string to check
     * @return boolean TRUE if string is valid UTF-8
     * @since 1.6
     */
    public static function compliant($str)  {
        if (strlen($str) == 0) {
            return TRUE;
        }
        // If even just the first character can be matched, when the /u
        // modifier is used, then it's valid UTF-8. If the UTF-8 is somehow
        // invalid, nothing at all will match, even if the string contains
        // some valid sequences
        return (preg_match('/^.{1}/us',$str,$ar) == 1);
    }

    /**
     * @param $str
     * @return string
     */
    public static function unescape($str) { 
        $str = rawurldecode($str); 
        preg_match_all("/(?:%u.{4})|.+/",$str,$r); 
        $ar = $r[0]; 
        foreach($ar as $k=>$v) { 
            if(substr($v,0,2) == "%u" && strlen($v) == 6) 
                //$ar[$k] = iconv("UCS-2","utf-8",pack("H4",substr($v,-4)));
                $ar[$k] = mb_convert_encoding(pack("H4",substr($v,-4)), "utf-8", "UCS-2");
        }
        return join("", $ar); 
    }

    /**
     * @param $str
     * @return string
     */
    public static function escape($str) { 
        preg_match_all("/[\x80-\xff].|[\x01-\x7f]+/", $str, $r);
        $ar = $r[0]; 
        foreach($ar as $k => $v) {
            if(ord($v[0]) < 128) 
                $ar[$k] = rawurlencode($v); 
            else 
                //$ar[$k] = "%u".bin2hex(iconv("utf-8","UCS-2",$v));
                $ar[$k] = "%u".bin2hex(mb_convert_encoding($v, "UCS-2", "utf-8"));
        }
        return join("", $ar); 
    }

    /**
     * @param $str
     * @return string
     */
    public static function encodeURIComponent($str){
        return urlencode($str);
    }

    /**
     * @param $str
     * @return array|mixed|string
     */
    public static function toGbk($str){
        if(is_object($str)){
            $str = (array)$str;
        }
        return is_array($str)
            ? array_map(array(__CLASS__, __METHOD__), $str)
            : mb_convert_encoding($str, 'gbk', 'utf-8')
            ;
    }

    /**
     * @param $str
     * @param string $charset
     * @return array|string
     */
    public static function utf8($str, $charset = 'gbk') {
        if(is_array($str)){
            foreach ($str as $k => $v) {
                if(is_array($v)){
                    $str[$k] = self::utf8($v);
                }else{
                    $str[$k] = mb_convert_encoding($v, 'utf-8', $charset);
                }
            }
        }else{
            $str = mb_convert_encoding($str, 'utf-8', $charset);
        }
        return $str;
    }

    /**
     * Determine if a given string starts with a given substring.
     *
     * @param  string  $haystack
     * @param  string|array  $needles
     * @return bool
     */
    public static function startsWith($haystack, $needles) {
        foreach ((array) $needles as $needle) {
            if ($needle != '' && mb_strpos($haystack, $needle) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if a given string ends with a given substring.
     *
     * @param  string  $haystack
     * @param  string|array  $needles
     * @return bool
     */
    public static function endsWith($haystack, $needles){
        foreach ((array) $needles as $needle) {
            if ((string) $needle === static::substr($haystack, -static::len($needle))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns the portion of string specified by the start and length parameters.
     *
     * @param  string  $string
     * @param  int  $start
     * @param  int|null  $length
     * @return string
     */
    public static function substr($string, $start, $length = null){
        return mb_substr($string, $start, $length, 'UTF-8');
    }
}
