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
 * @package inhere\library\process
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
     * SimpleObjectPool constructor.
     * @param ResourceInterface $objectFactory
     */
    public function __construct(ResourceInterface $objectFactory)
    {
        $this->objectFactory = $objectFactory;
        $this->pool = new \SplQueue();
    }

    /**
     * {@inheritdoc}
     */
    public function get($waiting = false)
    {
        if (!$this->pool->isEmpty()) {
            return $this->pool->pop();
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
