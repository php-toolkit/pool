<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/7/2
 * Time: 下午1:43
 */

namespace Inhere\Pool;

use Psr\Log\NullLogger;

/**
 * Class AbstractPool
 *  - 需要继承它，在自己的子类实现资源的创建和销毁. 以及一些自定义
 *
 * @package Inhere\Pool
 */
abstract class AbstractPool implements PoolInterface
{
    use FulledPoolTrait;

    /**
     * @var string The pool name
     */
    protected $name = 'default';

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
     * default 30 seconds
     * @var int
     */
    protected $expireTime = 30;

    /**
     * 自定义的资源配置(创建资源对象时可能会用到 e.g mysql 连接配置)
     * @var array
     */
    protected $options = [];

    /**
     * StdObject constructor.
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        foreach ($config as $property => $value) {
            $setter = 'set' . ucfirst($property);

            if (\method_exists($this, $setter)) {
                $this->$setter($value);
            } elseif (\property_exists($this, $property)) {
                $this->$property = $value;
            }
        }

        $this->init();
    }

    /**
     * init
     */
    protected function init()
    {
        $this->freeQueue = new \SplQueue();
        $this->busyQueue = new \SplObjectStorage();

        // fix mixSize
        if ($this->initSize > $this->maxSize) {
            $this->maxSize = $this->initSize;
        }
    }

    public function initPool()
    {
        // some works ...
        $this->prepare($this->initSize);

        if (!$this->logger) {
            $this->logger = new NullLogger();
        }
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
     * call resource(will auto get and put resource)
     * @param \Closure $closure
     * @return mixed
     */
    public function call(\Closure $closure)
    {
        $resource = $this->get();
        $result = $closure($resource);
        $this->put($resource);

        return $result;
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
     * @param mixed $obj
     * @return string
     */
    protected function genID($obj): string
    {
        if (\is_resource($obj)) {
            return (string)$obj;
        }

        if (\is_object($obj)) {
            return \spl_object_hash($obj);
        }

        return \md5(\json_encode($obj));
    }

    /**
     * 创建新的资源实例
     * @return mixed
     */
    abstract public function create();

    /**
     * 销毁资源实例
     * @param $obj
     * @return void
     */
    abstract public function destroy($obj);

    /**
     * 处理已过期的对象
     * @param $obj
     */
    protected function expire($obj)
    {
    }

    /**
     * 验证资源(eg. db connection)有效性
     * @param mixed $obj
     * @return bool
     */
    abstract protected function validate($obj): bool;

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
     * release pool
     */
    public function __destruct()
    {
        $this->clear();
    }

    /**
     * @return \SplQueue
     */
    public function getFreeQueue(): \SplQueue
    {
        return $this->freeQueue;
    }

    /**
     * @param \SplQueue $freeQueue
     */
    public function setFreeQueue(\SplQueue $freeQueue)
    {
        $this->freeQueue = $freeQueue;
    }

    /**
     * @return \SplObjectStorage
     */
    public function getBusyQueue(): \SplObjectStorage
    {
        return $this->busyQueue;
    }

    /**
     * @param \SplObjectStorage $busyQueue
     */
    public function setBusyQueue(\SplObjectStorage $busyQueue)
    {
        $this->busyQueue = $busyQueue;
    }

    /**
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @param array $options
     */
    public function setOptions(array $options)
    {
        $this->options = $options;
    }
}
