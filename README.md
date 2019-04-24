## 概述

一些基础php组件

1. 配置
1. 缓存
3. 动态方法管理
4. 调试工具
5. 服务定位容器
6. 错误管理
7. 事件管理
8. 日志管理
9. 网络基础库
10. 安全工具
11. 其它工具

## 安装

```
composer install scalpel/core
```

## Usage

## tool

**ListFilter**

根据keys过滤数组元素
```
$listFilter->filter(sourceArray, keys)
```

example
```
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
        'id|int', 'title@name',
        ['images', ['src', 'id'], true],
        'images.id@imgId'
    ]);

    var_dump($return);
```

keys语法: array[item, item], item格式支持:

1. string 字段
2. string.string
3. array[item, keys]

## Todo

1. 详细列出 di的配置形式
2. 处理Base::createObject()的参数或是丢弃此类, 统一使用DI容器创建对象

````
Module::__constuct($id, $app);

admin => [
   class => X
   var => Value
]

Base::createObject(Array, [id, this])
# 一旦配置为数组, 导致丢弃第二参数导致错误

````

## Base::createObject()
1. 参数1为数组, 丢弃第二个参数
2. 参数1为字串, 使用第二个参数用于构造函数

## DI::createObject
1. 不为callback, 丢弃第二个参数
1. 为callback, 使用第二个参数传递到callback
