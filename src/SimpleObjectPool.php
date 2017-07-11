<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/6/24
 * Time: 下午7:17
 */

namespace inhere\pool;

/**
 * Class SimpleObjectPool
 * @package inhere\library\process
 */
class SimpleObjectPool implements PoolInterface
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
    public function get($blocking = false)
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
