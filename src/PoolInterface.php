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
interface PoolInterface  extends LitePoolInterface
{
    /**
     * @return int
     */
    public function getFreeCount(): int;

    /**
     * @return int
     */
    public function getBusyCount(): int;

    /**
     * @return int
     */
    public function count(): int;
}
