<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2018-12-12
 * Time: 23:12
 */

namespace Toolkit\Pool;

/**
 * Interface LitePoolInterface
 * @package Toolkit\Pool
 */
interface LitePoolInterface
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
