<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/7/2
 * Time: 下午1:43
 */

namespace Inhere\Pool;

/**
 * Class PoolAbstracter
 *  - 需要继承它，在自己的子类实现资源的创建和销毁. 以及一些自定义
 *
 * @package Inhere\Pool
 */
abstract class AbstractPool implements PoolInterface
{
    /**
     * (Free) available resource queue
     * @var \SplQueue
     */
    private $freeQueue;

    /**
     * (Busy) in use resource
     * @var \SplObjectStorage
     */
    private $busyQueue;

    /**
     * default 30 seconds
     * @var int
     */
    public $expireTime = 30;

    /**
     * Initialize the pool size
     * @var int
     */
    private $initSize = 0;

    /**
     * 扩大的增量(当资源不够时，一次增加资源的数量)
     * @var int
     */
    private $stepSize = 1;

    /**
     * The maximum size of the pool resources
     * @var int
     */
    private $maxSize = 100;

    /**
     * @var bool
     */
    private $waiting = true;

    /**
     * the waiting timeout(ms) when get resource
     * @var int
     */
    private $timeout = 1000;

    /**
     * 自定义的资源配置(创建资源对象时可能会用到 e.g mysql 连接配置)
     * @var array
     */
    protected $options = [];

    /**
     * StdObject constructor.
     * @param array $config
     * @param array $options
     */
    public function __construct(array $config = [], array $options = [])
    {
        foreach ($config as $property => $value) {
            $this->$property = $value;
        }

        if ($options) {
            $this->setOptions($options);
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

    /**
     * {@inheritdoc}
     * @param bool $waiting 当没有资源可用时，是否等待
     *  true  挂起，等待空闲连接
     *  false 返回 null
     * @return mixed
     */
    public function get($waiting = null)
    {
        // 还有可用资源
        if (!$this->freeQueue->isEmpty()) {
            $res = $res = $this->freeQueue->pop();

            // 无可用的空闲资源， 并且资源池已满
        } elseif (!$this->hasFree() && ($this->count() >= $this->maxSize)) {
            $waiting = $waiting ?? $this->waiting;

            if (!$waiting) {
                return null;
            }

            $res = $this->waitingAndGet();

            // 无可用资源, 资源池未满
        } else {
            // 创建新的资源
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
    abstract protected function waitingAndGet();

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
    public function prepare($size)
    {
        if ($size <= 0) {
            return 0;
        }

        for ($i = 0; $i < $size; $i++) {
            $res = $this->create();
//            var_dump($i, $size, $res);
            $this->getFreeQueue()->push($res);
        }

        return $size;
    }

    /**
     * 创建新的资源实例
     * @return mixed
     */
    abstract public function create();

    /**
     * 销毁资源实例
     * @param $resource
     * @return void
     */
    abstract public function destroy($resource);

    /**
     * 处理已过期的对象
     * @param $obj
     */
    protected function expire($obj): void
    {

    }

    /**
     * 验证对象有效性
     * @param mixed $obj
     * @return bool
     */
    protected function validate($obj): bool
    {
        return true;
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
    public function countFree()
    {
        return $this->freeQueue->count();
    }

    /**
     * @return int
     */
    public function countBusy()
    {
        return $this->busyQueue->count();
    }

    /**
     * @return bool
     */
    public function hasFree()
    {
        return $this->freeQueue->count() > 0;
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
     * @return int
     */
    public function getExpireTime(): int
    {
        return $this->expireTime;
    }

    /**
     * @param int $expireTime
     */
    public function setExpireTime(int $expireTime)
    {
        $this->expireTime = $expireTime;
    }

    /**
     * @return int
     */
    public function getInitSize(): int
    {
        return $this->initSize;
    }

    /**
     * @param int $initSize
     */
    public function setInitSize(int $initSize)
    {
        $this->initSize = $initSize < 0 ? 0 : $initSize;
    }

    /**
     * @return int
     */
    public function getStepSize(): int
    {
        return $this->stepSize;
    }

    /**
     * @param int $stepSize
     */
    public function setStepSize(int $stepSize)
    {
        $this->stepSize = $stepSize < 1 ? 1 : $stepSize;
    }

    /**
     * @return int
     */
    public function getMaxSize(): int
    {
        return $this->maxSize;
    }

    /**
     * @param int $maxSize
     * @throws \InvalidArgumentException
     */
    public function setMaxSize(int $maxSize)
    {
        if ($maxSize < 1) {
            throw new \InvalidArgumentException('The resource pool max size cannot lt 1');
        }

        $this->maxSize = $maxSize;
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
     * @return bool
     */
    public function isWaiting(): bool
    {
        return $this->waiting;
    }

    /**
     * @param bool $waiting
     */
    public function setWaiting($waiting)
    {
        $this->waiting = (bool)$waiting;
    }

    /**
     * @return int
     */
    public function getTimeout(): int
    {
        return $this->timeout;
    }

    /**
     * @param int $timeout
     */
    public function setTimeout(int $timeout)
    {
        $this->timeout = $timeout;
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
