<?php

namespace j\base;

/**
 * Class ApiClassResolver
 * @package j\base
 */
class ApiClassResolver {

    /**
     * @var LoaderInterface[]|callable[]
     */
    protected $loaders =[];

    public $separator = '.';
    public $resolver;
    public $defaultClass = "Actions";

    protected $actionMap = [];
    protected $fallbackMap = [];

    private $cache;

    /**
     * @var string
     */
    public $ns = '';
    public $classSuffix = '';

    /**
     * @param LoaderInterface|callable $loader
     */
    function addLoader($loader){
        $this->loaders[] = $loader;
    }

    /**
     * @param callable[]|LoaderInterface[] $loaders
     */
    public function setLoaders($loaders){
        $this->loaders = (array)$loaders;
    }

    /**
     * @param $api
     * @param $version
     * @return boolean|string|array[class, action]
     */
    function load($api, $version = ''){
        $api = trim($api, $this->separator);
        if(isset($this->cache[$api])){
            return $this->cache[$api];
        }

        if($plug = $this->getClassWithMap($api)){
            return $plug;
        }

        $names = (array)$this->normalizeName($api);

        if(!$this->loaders){
            $this->loaders[] = [$this, 'loadClass'];
        }

        foreach($names as $name){
            foreach($this->loaders as $loader){
                if($loader instanceof LoaderInterface){
                    if($plug = $loader->load($name)){
                        $this->cache[$name] = $plug;
                        return $plug;
                    }
                } elseif($plug = $loader($name, $version, $api)) {
                    $this->cache[$name] = $plug;
                    return $plug;
                }
            }
        }

        if($plug = $this->getClassWithMap($api, true)){
            return $plug;
        }

        return true;
    }

    /**
     * @param $name
     * @param string $version
     * @return array|bool
     */
    public function loadClass($name, $version = ''){
        $ns1 = $this->ns . ($version ? $version . "\\" : '');
        list($className, $ns2, $action) = $name;
        $apiClass = $ns1 . $ns2 . ucfirst($className) . $this->classSuffix;
        if(class_exists($apiClass)){
            return [$apiClass, $action];
        } else {
            return false;
        }
    }

    /**
     * @param $name
     * @return array
     */
    function normalizeName($name){
        $name = trim($name, $this->separator);

        if(!$this->resolver){
            $this->resolver = array($this, 'resolve');
        }

        return call_user_func($this->resolver, $name, $this);
    }

    /**
     * 分解name
     * @param $name
     * @return array
     */
    function resolve($name){
        $names = [];
        $action = null;
        $path = explode('/', $name);
        while(count($path) > 0){
            $className = array_pop($path);
            $ns = $path ? (implode("\\", $path)) . "\\" : '';
            $names[] = [$className, $ns, $action];

            $action = ($action ? ($action . $this->separator) : $action) . $className;
            $action .= $className;

            if($this->defaultClass){
                $names[] = [$this->defaultClass, $ns, $className];
            }
        }
        return $names;
    }

    private function getClassWithMap($id, $isFallback = false){
        $target = $this->getTargetMap($isFallback);
        if(!isset($target[$id])) {
            return null;
        }
        return $target[$id];
    }

    private function & getTargetMap($isFallback = false) {
        if($isFallback){
            return $this->fallbackMap;
        } else {
            return $this->actionMap;
        }
    }
}
