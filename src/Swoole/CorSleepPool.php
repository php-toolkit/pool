<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-09-08
 * Time: 10:55
 */

namespace Inhere\Pool\Swoole;

use Inhere\Pool\AbstractPool;
use Swoole\Coroutine;

/**
 * Class ResourcePool - by Coroutine implement
 * @package Inhere\Pool\Swoole
 */
abstract class CorSleepPool extends AbstractPool
{
    /**
     * check Interval time(ms)
     * @var int
     */
    protected $checkInterval = 20;

    protected function init()
    {
        parent::init();

        $this->prepare($this->getInitSize());
    }

    /**
     * 等待并返回可用资源
     * @return bool|mixed
     */
    protected function waitingAndGet()
    {
        $timer = 0;
        $timeout = $this->getTimeout();
        $interval = $this->checkInterval;
        $intervalSecond = $this->checkInterval/1000;

        while ($timer <= $timeout) {
            // 等到了可用的空闲资源
            if ($res = $this->getFreeQueue()->pop()) {
                return $res;
            }

            $timer += $interval;
            // 无空闲资源可用， 挂起协程
            Coroutine::sleep($intervalSecond);
        }

        throw new \RuntimeException("Waiting timeout($timeout ms) for get resource.");
    }
}
