<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2018-11-28
 * Time: 13:37
 */

namespace Toolkit\Pool;

/**
 * Class SPLQueuePool - pool
 * @package Toolkit\Pool
 */
abstract class SPLQueuePool extends AbstractPool
{
    /**
     * (Free) available resource queue
     * @var \SplQueue
     */
    protected $freeQueue;

    /**
     * (Busy) in use resource
     * @var \SplObjectStorage
     */
    protected $busyQueue;

    /**
     * init
     */
    protected function init()
    {
        $this->freeQueue = new \SplQueue();
        $this->busyQueue = new \SplObjectStorage();

        parent::init();
    }

    /**
     * 预(创建)准备资源
     * @param int $size
     * @return int
     */
    protected function prepare(int $size): int
    {
        if ($size <= 0) {
            return 0;
        }

        for ($i = 0; $i < $size; $i++) {
            $res = $this->create();
            // var_dump($i, $size, $res);
            $this->getFreeQueue()->push($res);
        }

        return $size;
    }

    /**
     * {@inheritdoc}
     * @return mixed
     * @throws \RuntimeException
     */
    public function get()
    {
        // There are also resources available
        if (!$this->freeQueue->isEmpty()) {
            $res = $this->freeQueue->pop();

            // add to busy pool
            $this->busyQueue->attach($res);

            return $res;
        }

        // No available free resources, and the resource pool is full. waiting ...
        if (!$this->hasFree() && ($this->count() >= $this->maxSize)) {
            if ($this->waitTimeout === 0) {
                // return null;
                throw new \RuntimeException(
                    "Server busy, no resources available.(The pool has been overflow max value: {$this->maxSize})"
                );
            }

            $res = $this->wait();

            // No resources available, resource pool is not full
        } else {
            // create new resource
            $this->prepare($this->stepSize);
            $res = $this->freeQueue->pop();
        }

        // add to busy pool
        $this->busyQueue->attach($res);

        return $res;
    }

    /**
     * 等待并返回可用资源
     * @return bool|mixed
     */
    abstract protected function wait();

    /**
     * {@inheritdoc}
     */
    public function put($resource)
    {
        // remove from busy queue
        $this->busyQueue->detach($resource);

        // push to free queue
        $this->freeQueue->push($resource);
    }

    /**
     * release pool
     */
    public function clear()
    {
        // clear free queue
        while ($obj = $this->getFreeQueue()->pop()) {
            $this->destroy($obj);
        }

        $this->busyQueue->removeAll($this->busyQueue);

        $this->freeQueue = null;
        $this->busyQueue = null;
    }


    /**
     * @return int
     */
    public function count(): int
    {
        return \count($this->busyQueue) + \count($this->freeQueue);
    }

    /**
     * @return int
     */
    public function freeCount(): int
    {
        return $this->freeQueue->count();
    }

    /**
     * @return int
     */
    public function busyCount(): int
    {
        return $this->busyQueue->count();
    }

    /**
     * @return bool
     */
    public function hasFree(): bool
    {
        return $this->freeQueue->count() > 0;
    }

    /**
     * @return bool
     */
    public function hasBusy(): bool
    {
        return $this->busyQueue->count() > 0;
    }

    /**
     * @return \SplQueue
     */
    public function getFreeQueue(): \SplQueue
    {
        return $this->freeQueue;
    }

    /**
     * @return \SplObjectStorage
     */
    public function getBusyQueue(): \SplObjectStorage
    {
        return $this->busyQueue;
    }

}
