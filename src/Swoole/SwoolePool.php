<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2018/2/6 0006
 * Time: 20:42
 */

namespace Inhere\Pool\Swoole;

use Inhere\Pool\AbstractPool;
use Swoole\Timer;

/**
 * Class SwoolePool
 * @package Inhere\Pool\Swoole
 */
abstract class SwoolePool extends AbstractPool
{
    /**
     * @var int The max free waiting time(minutes) the free resource - 资源最大空闲时间
     */
    protected $freeTimeout = 5;

    /**
     * @var int
     */
    protected $checkInterval = 5;

    /**
     * @var array 用于检查空闲时间的定时器列表
     */
    protected $timers = [];

    /**
     * pool checker
     */
    public function poolChecker()
    {
        Timer::tick($this->checkInterval * 1000, function () {

        });
    }
}
