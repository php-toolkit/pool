<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/7/2
 * Time: 下午1:43
 */

namespace Inhere\Pool;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Class PoolAbstracter
 *  - 需要继承它，在自己的子类实现资源的创建和销毁. 以及一些自定义
 *
 * @package Inhere\Pool
 */
abstract class AbstractPool implements PoolInterface
{
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
     * @var array[]
     * [
     *  'res id' => [
     *      'createdAt' => int,
     *  ]
     * ]
     */
    protected $metadata = [];

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
     * @var int Minimum free connection. 最小空闲连接
     */
    private $minFree = 3;

    /**
     * @var int Maximum free connection. 最大空闲连接
     */
    private $maxFree = 10;

    /**
     * Maximum waiting time(ms) when get connection.
     * > 0  waiting time(ms)
     * 0    Do not wait
     * -1   Always waiting
     * @var int
     */
    private $maxWait = 3000;

    /**
     * @var int The max free time(minutes) the free resource - 资源最大空闲时间
     */
    protected $maxFreeTime = 10;

    /**
     * @var bool Whether validate resource on get
     */
    private $validateOnGet = true;

    /**
     * @var bool Whether validate resource on put
     */
    private $validateOnPut = true;

    /**
     * 自定义的资源配置(创建资源对象时可能会用到 e.g mysql 连接配置)
     * @var array
     */
    protected $options = [];

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * StdObject constructor.
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        foreach ($config as $property => $value) {
            $this->$property = $value;
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
     * @param bool $waiting 当没有资源可用时，是否等待
     *  true  挂起，等待空闲连接
     *  false 返回 null
     * @return mixed
     */
    public function get()
    {
        // There are also resources available
        if (!$this->freeQueue->isEmpty()) {
            $res = $res = $this->freeQueue->pop();

            // add to busy pool
            $this->busyQueue->attach($res);

            return $res;
        }

        // No available free resources, and the resource pool is full. waiting ...
        if (!$this->hasFree() && ($this->count() >= $this->maxSize)) {
            if ($this->maxWait === 0) {
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
    protected function prepare(int $size)
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
     * @param mixed $resource
     * @return string
     */
    protected function genID($resource)
    {
        if (\is_resource($resource)) {
            return (string)$resource;
        }

        if (\is_object($resource)) {
            return spl_object_hash($resource);
        }

        return md5(json_encode($resource));
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
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return LoggerInterface
     */
    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    /**
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @return int
     */
    public function getMinFree(): int
    {
        return $this->minFree;
    }

    /**
     * @param int $minFree
     */
    public function setMinFree(int $minFree)
    {
        $this->minFree = $minFree;
    }

    /**
     * @return int
     */
    public function getMaxFree(): int
    {
        return $this->maxFree;
    }

    /**
     * @param int $maxFree
     */
    public function setMaxFree(int $maxFree)
    {
        $this->maxFree = $maxFree;
    }

    /**
     * @return int
     */
    public function getMaxWait(): int
    {
        return $this->maxWait;
    }

    /**
     * @param int $maxWait
     */
    public function setMaxWait(int $maxWait)
    {
        $this->maxWait = $maxWait;
    }

    /**
     * @return int
     */
    public function getMaxFreeTime(): int
    {
        return $this->maxFreeTime;
    }

    /**
     * @param int $maxFreeTime
     */
    public function setMaxFreeTime(int $maxFreeTime)
    {
        $this->maxFreeTime = $maxFreeTime;
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
     * @return bool
     */
    public function isValidateOnGet(): bool
    {
        return $this->validateOnGet;
    }

    /**
     * @param bool $validateOnGet
     */
    public function setValidateOnGet(bool $validateOnGet)
    {
        $this->validateOnGet = $validateOnGet;
    }

    /**
     * @return bool
     */
    public function isValidateOnPut(): bool
    {
        return $this->validateOnPut;
    }

    /**
     * @param bool $validateOnPut
     */
    public function setValidateOnPut(bool $validateOnPut)
    {
        $this->validateOnPut = $validateOnPut;
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
