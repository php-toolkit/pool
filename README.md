# 资源池

> 资源池使用在 **常住进程的服务** 中才有用。比如用 swoole/workman 创建的应用

使用池可实现 数据库连接池、redis连接池等，减少对服务的过多的连接/断开带来的额外资源消耗

## 项目地址

- **git@osc** https://git.oschina.net/inhere/php-resource-pool.git
- **github** https://github.com/inhere/php-resource-pool.git

**注意：**

- master 分支是要求 `php >= 7` 的

## 安装

- 使用 composer

编辑 `composer.json`，在 `require` 添加

```
"inhere/resource-pool": "dev-master"
```

然后执行: `composer update`

- 直接拉取

```
git clone https://git.oschina.net/inhere/php-resource-pool.git // git@osc
git clone https://github.com/inhere/php-resource-pool.git // github
```
