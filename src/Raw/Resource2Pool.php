<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/7/2
 * Time: 下午1:50
 */

namespace Inhere\Pool\Raw;

use Inhere\Pool\AbstractPool;
use Inhere\Pool\FactoryInterface;

/**
 * Class ResourcePool2
 * - 通过设置的资源工厂类实现资源的创建和销毁
 *
 * @package Inhere\Pool\Raw
 */
class Resource2Pool extends AbstractPool
{
    /**
     * @var FactoryInterface
     */
    private $factory;

    protected function init()
    {
        parent::init();

        if ($this->factory) {
            $this->prepare($this->getInitSize());
        }
    }

    /**
     * 等待并返回可用资源
     * @return bool|mixed
     */
    protected function waitingAndGet()
    {
        $timer = 0;
        $timeout = $this->getTimeout();
        $interval = 50;
        $uSleep = $interval * 1000;

        while ($timer <= $timeout) {
            // 等到了可用的空闲资源
            if ($res = $this->getFreeQueue()->pop()) {
                return $res;
            }

            $timer += $interval;
            usleep($uSleep);
        }

        return false;
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

        // 预准备资源
        $this->prepare($this->getInitSize());

        return $this;
    }

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
}
