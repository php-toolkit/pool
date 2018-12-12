<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/6/24
 * Time: 下午7:17
 */

namespace Toolkit\Pool;

/**
 * Class UnlimitedPool - 无(大小)限制的资源池，没有资源就创建
 * @package Inhere\Library\process
 */
class UnlimitedPool implements LitePoolInterface
{
    /**
     * @var \SplQueue
     */
    private $queue;

    /**
     * @var \Closure
     */
    private $creator;

    /**
     * @var int
     */
    private $maxSize;

    /**
     * class constructor.
     * @param int $maxSize
     */
    public function __construct(int $maxSize = 100)
    {
        $this->queue = new \SplQueue();
        $this->maxSize = $maxSize;
    }

    /**
     * @param \Closure $creator
     */
    public function setCreator(\Closure $creator): void
    {
        $this->creator = $creator;
    }

    /**
     * {@inheritdoc}
     * @throws \RuntimeException
     */
    public function get()
    {
        if (!$this->queue->isEmpty()) {
            return $this->queue->pop();
        }

        if ($this->maxSize > 0 && $this->count() >= $this->maxSize) {
            throw new \RuntimeException(
                "Server busy, no resources available.(The pool has been overflow max value: {$this->maxSize})"
            );
        }

        return ($this->creator)();
    }

    /**
     * @param $obj
     */
    public function put($obj)
    {
        $this->queue->push($obj);
    }

    /**
     * Empty the resource pool - Release all connections
     */
    public function clear()
    {
        while (!$this->queue->isEmpty()) {
            $this->queue->pop();
        }

        $this->queue = null;
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return $this->queue->count();
    }

    /**
     * release pool
     */
    public function __destruct()
    {
        $this->clear();
    }

}
