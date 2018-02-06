<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-09-08
 * Time: 15:11
 */

namespace Inhere\Pool\Swoole\Client;

use Inhere\Pool\Swoole\CoSuspendPool;
use Swoole\Coroutine\Redis;

/**
 * Class CoRedisPool
 * @package Inhere\Pool\Swoole\Client
 */
class CoRedisPool extends CoSuspendPool
{
    /**
     * 创建新的资源实例
     * @return mixed
     */
    public function create()
    {
        $rds = new Redis();

        // debug('coId:' . Coroutine::id() . ' will create new redis connection');

        $rds->connect('redis', 6379);

        // debug('coId:' . Coroutine::id() . ' a new redis connection created');

        return $rds;
    }

    /**
     * 销毁资源实例
     * @param $resource
     * @return void
     */
    public function destroy($resource)
    {
        // unset($resource);
    }
}
