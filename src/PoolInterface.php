<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/6/24
 * Time: 下午7:16
 */

namespace Inhere\Pool;

/**
 * Class PoolInterface
 * @package Inhere\Pool
 */
interface PoolInterface
{
    /**
     * 获取资源
     * @param bool $waiting 是否等待，当没有资源可用时
     * @return mixed
     */
    public function get($waiting = null);

    /**
     * 返还资源到资源池
     * @param mixed $resource
     */
    public function put($resource);
}
