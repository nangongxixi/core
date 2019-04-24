<?php

spl_autoload_register(function($class){
    echo "load class with autoload function\n";
    $file = __DIR__ . "/" . str_replace('\\', '/', $class) . ".php";
    if(!file_exists($file)){
        return false;
    }
    include($file);
    return class_exists($class);
});

$file = __DIR__ . "/object.cache.php";
if(!file_exists($file)){
    echo "Object from new\n";
    $test = new test\TestObject();
    file_put_contents($file, serialize($test));
} else {
    echo "Object from cache\n";
    $test = unserialize(file_get_contents($file));
}

print_r($test);