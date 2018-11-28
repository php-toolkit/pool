# 资源池

[![License](https://img.shields.io/packagist/l/toolkit/pool.svg?style=flat-square)](LICENSE)
[![Php Version](https://img.shields.io/badge/php-%3E=7.0-brightgreen.svg?maxAge=2592000)](https://packagist.org/packages/toolkit/pool)
[![Latest Stable Version](http://img.shields.io/packagist/v/toolkit/pool.svg)](https://packagist.org/packages/toolkit/pool)

> 资源池使用在 **常住进程的服务** 中才有用。比如用 swoole/workman 创建的应用

使用池可实现 数据库连接池、redis连接池等，减少对服务的过多的连接/断开带来的额外资源消耗。

- 基于swoole的实现  

本仓库主要是做一些关于连接池的基础接口方法的抽象定义，并没有完整具体的实现。

> 具体的实现请查看： https://github.com/swokit/connection-pool.git

## 项目地址

- **github** https://github.com/php-toolkit/pool.git

## 安装

- 使用 `composer require toolkit/pool`
- 使用 `composer.json`

```
"toolkit/pool": "dev-master"
```

然后执行: `composer update`

- 直接拉取

```
git clone https://git.oschina.net/inhere/php-resource-pool.git // git@osc
git clone https://github.com/inhere/php-resource-pool.git // github
```

## 使用

```php

use Toolkit\Pool\Raw\ResourcePool;

$rpl = new ResourcePool([
    'initSize' => 2,
    'maxSize' => 2,
    'driverOptions' => [

    ],
]);

$rpl->setCreator(function () {
    $obj = new \stdClass();
    $obj->name = 'test';

    return $obj;
})
    ->setDestroyer(function ($obj) {
    echo "call resource destroyer.\n";
});

var_dump($rpl);

$obj1 = $rpl->get();
$obj2 = $rpl->get();
$obj3 = $rpl->get();

var_dump($obj1, $obj2, $obj3,$rpl);

$rpl->put($obj1);
$rpl->put($obj2);

var_dump($rpl);

$rpl->call(function ($obj) {
   echo " $obj->name\n";
});

var_dump($rpl);
```

## License

[MIT](LICENSE)
