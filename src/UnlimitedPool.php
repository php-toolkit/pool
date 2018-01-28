<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/6/24
 * Time: 下午7:17
 */

namespace Inhere\Pool;

/**
 * Class UnlimitedPool - 无(大小)限制的资源池， 没有资源就创建
 * @package Inhere\Library\process
 */
class UnlimitedPool implements PoolInterface
{
    /**
     * @var FactoryInterface
     */
    private $objectFactory;

    /**
     * @var \SplQueue
     */
    private $pool;

    /**
     * @var int
     */
    private $maxSize;

    /**
     * SimpleObjectPool constructor.
     * @param FactoryInterface $objectFactory
     * @param int $maxSize
     */
    public function __construct(FactoryInterface $objectFactory, int $maxSize = 100)
    {
        $this->objectFactory = $objectFactory;
        $this->pool = new \SplQueue();
        $this->maxSize = $maxSize;
    }

    /**
     * {@inheritdoc}
     */
    public function get($waiting = false)
    {
        if (!$this->pool->isEmpty()) {
            return $this->pool->pop();
        }

        if ($this->maxSize > 0 && $this->count() >= $this->maxSize) {
            throw new \RuntimeException("The created resource has been overflow max value: {$this->maxSize}");
        }

        return $this->objectFactory->create();
    }

    /**
     * @param $obj
     */
    public function put($obj)
    {
        $this->pool->push($obj);
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return $this->pool->count();
    }

    /**
     * @return FactoryInterface
     */
    public function getObjectFactory(): FactoryInterface
    {
        return $this->objectFactory;
    }

    /**
     * release pool
     */
    public function __destruct()
    {
        $this->clear();
    }

    /**
     * Empty the resource pool - Release all connections
     */
    public function clear()
    {
        foreach ($this->pool as $obj) {
            $this->objectFactory->destroy($obj);
        }

        $this->pool = null;
    }
}
