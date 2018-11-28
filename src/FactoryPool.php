<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2018/2/6 0006
 * Time: 23:33
 */

namespace Toolkit\Pool;

/**
 * Class ConnectionPool
 * @package Toolkit\Pool
 */
class FactoryPool extends SPLQueuePool
{
    /**
     * @var FactoryInterface
     */
    private $factory;

    /**
     * 创建新的资源实例
     * @return mixed
     */
    public function create()
    {
        return $this->factory->create();
    }

    /**
     * 销毁资源实例
     * @param $resource
     * @return void
     */
    public function destroy($resource)
    {
        $this->factory->destroy($resource);
    }

    /**
     * release pool
     */
    public function clear()
    {
        $this->factory = null;

        parent::clear();
    }

    /**
     * @return FactoryInterface
     */
    public function getFactory()
    {
        return $this->factory;
    }

    /**
     * @param FactoryInterface $factory
     * @return $this
     */
    public function setFactory(FactoryInterface $factory)
    {
        $this->factory = $factory;

        return $this;
    }

    /**
     * 等待并返回可用资源
     * @return bool|mixed
     */
    protected function wait()
    {
        // TODO: Implement waitingAndGet() method.
    }

    /**
     * 验证资源(eg. db connection)有效性
     * @param mixed $obj
     * @return bool
     */
    protected function validate($obj): bool
    {
        // TODO: Implement validate() method.
    }
}
