<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/6/24
 * Time: 下午7:16
 */

namespace Toolkit\Pool;

/**
 * Class PoolInterface
 * @package Toolkit\Pool
 */
interface PoolInterface
{
    /**
     * Access to resource
     * @return mixed
     */
    public function get();

    /**
     * Return resource to the pool
     * @param mixed $resource
     */
    public function put($resource);

    /**
     * Empty the resource pool - Release all connections
     */
    public function clear();
}
