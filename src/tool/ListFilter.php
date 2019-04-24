<?php

namespace j\tool;

use function array_pop;
use ArrayAccess;
use function call_user_func;
use Closure;
use function explode;
use function var_dump;

/**
 * Class ListFilter
 * @package j\tool
 */
class ListFilter {

    /**
     * @var Modifier
     */
    public $modifier;

    /**
     * @return Modifier
     */
    function getModifier() {
        if(!isset($this->modifier)){
            $this->modifier = new Modifier();
        }
        return $this->modifier;
    }

    /**
     * @param string[]|array[] $keys
     * @param $info
     * @param $filterEmpty
     * @throws
     * @return array
     */
    public function filterRow($info, $keys, $filterEmpty = false){
        $modifier = $this->getModifier();

        $row = [];
        foreach ($keys as $key) {
            if($key instanceof Closure){
                list($key, $value) = call_user_func($key, $info);
                $row[$key] = $value;
            }elseif(is_array($key)){
                // 递归处理子过滤
                // key = [keyName, [key1, key2], isList]
                list($_key, $_keys) = $key;
                $isList = isset($key[2]) && $key[2];
                list($key, $alias) = $this->getAlias($_key);
                if($value = $this->gav($info, $key)){
                    if($isList){
                        $row[$alias] = $this->filter($value, $_keys);
                    } else {
                        $row[$alias] = $this->filterRow($value, $_keys, $filterEmpty);
                    }
                }

            }elseif(strpos($key, '|')){
                list($_key, $type) = explode("|", $key, 2);
                list($key, $alias) = $this->getAlias($_key);
                $row[$alias] = $type
                    ? $modifier->parse($this->gav($info, $key), $type)
                    : $this->gav($info,$key)
                ;
            }else{
                list($key, $alias) = $this->getAlias($key);
                $row[$alias] = $this->gav($info, $key);
            }
        }
        return $row;
    }

    private function getAlias($key){
        if(strpos($key, '@')){
            return explode("@", $key, 2);
        } elseif(strpos($key, '.')) {
            $parts = explode('.', $key);
            return [$key, array_pop($parts)];
        } else {
            return [$key, $key];
        }
    }

    private function gav($info, $key, $default = null){
        if(strpos($key, '.')){
            $keys = explode(".", $key);
            while($_key = array_shift($keys)){
                $info = $this->gav($info, $_key, $default);
                if(!$info){
                    return $default;
                }
            }
            return $info;
        } else {
            if(is_array($info) && array_key_exists($key, $info) || $info instanceof ArrayAccess){
                return $info[$key];
            } elseif(isset($info->{$key})){
                return $info->{$key};
            } else {
                return $default;
            }
        }
    }

    /**
     * @param $data
     * @param array $keys
     * @param callable $callback
     * @return array
     */
    function filter($data, $keys = ['id', 'title', 'image'], $callback = null){
        $list = array();
        if(!$callback){
            foreach ($data as $key => $item) {
                $item = $this->filterRow($item, $keys);
                $list[$key] = $item;
            }
        } else {
            foreach ($data as $key => $item) {
                $item = $this->filterRow($item, $keys);
                if($callback($item)){
                    $list[$key] = $item;
                }
            }
        }

        return $list;
    }
}

/**
 *
    $array = [
        [
            'id' => 1, 'title' => "title", 'images' => [
                ['title' => "title", "src" => '1233', 'id' => 2],
                ['title' => "title", "src" => '1233', 'id' => 2],
            ]
        ],
        [
            'id' => 1, 'title' => "title", 'images' => [
                ['title' => "title", "src" => '1233', 'id' => 2],
                ['title' => "title", "src" => '1233', 'id' => 2],
            ]
        ]
    ];


    $listFilter = new ListFilter();
    $return = $listFilter->filter($array, [
        'id', 'title',
        ['images', ['src', 'id'], true]
    ]);

    var_dump($return);
 */
