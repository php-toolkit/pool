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
     * @var ResourceInterface
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
     * @param ResourceInterface $objectFactory
     */
    public function __construct(ResourceInterface $objectFactory, $maxSize = 100)
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
    public function count()
    {
        return $this->pool->count();
    }

    /**
     * @return ResourceInterface
     */
    public function getObjectFactory()
    {
        return $this->objectFactory;
    }

    /**
     * release pool
     */
    public function __destruct()
    {
        foreach ($this->pool as $obj) {
            $this->objectFactory->destroy($obj);
        }

        $this->pool = null;
    }
}
