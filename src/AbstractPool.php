<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/7/2
 * Time: 下午1:43
 */

namespace Toolkit\Pool;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Class AbstractPool
 *  - 需要继承它，在自己的子类实现资源的创建和销毁. 以及一些自定义
 *
 * @package Toolkit\Pool
 */
abstract class AbstractPool implements PoolInterface
{
    use FulledPoolTrait;

    /**
     * @var string The pool name
     */
    protected $name = 'default';

    /**
     * metadata for connections
     * @var array[]
     * [
     *  'res id' => [
     *      'createAt' => int,
     *      'activeAt' => int, // Recent active time - 最近活跃时间
     *  ]
     * ]
     */
    protected $metas = [];

    /**
     * default 30 seconds
     * @var int
     */
    protected $expireTime = 30;

    /**
     * Initialize the pool size
     * @var int
     */
    protected $initSize = 0;

    /**
     * 扩大的增量(当资源不够时，一次增加资源的数量)
     * @var int
     */
    protected $stepSize = 1;

    /**
     * The maximum size of the pool resources
     * @var int
     */
    protected $maxSize = 200;

    /**
     * Maximum waiting time(ms) when get connection. - 获取资源等待超时时间
     * > 0  waiting time(ms)
     * 0    Do not wait
     * -1   Always waiting
     * @var int
     */
    protected $waitTimeout = 3000;

    /**
     * @var int The max free time(minutes) the free resource - 资源最大生命时长
     */
    protected $maxLifetime = 30;

    /**
     * @var LoggerInterface
     */
    protected $logger;

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
        return 0;
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
        // TODO ..
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
        return $this->getFreeCount() + $this->getBusyCount();
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
    public function getMaxLifetime(): int
    {
        return $this->maxLifetime;
    }

    /**
     * @param int $maxLifetime
     */
    public function setMaxLifetime(int $maxLifetime)
    {
        $this->maxLifetime = $maxLifetime;
    }

    /**
     * @param string $resId
     * @return array
     */
    public function getMeta(string $resId): array
    {
        return $this->metas[$resId] ?? [];
    }

    /**
     * @return array[]
     */
    public function getMetas(): array
    {
        return $this->metas;
    }

    /**
     * @param int $maxWait
     */
    public function setWaitTimeout(int $maxWait)
    {
        $this->waitTimeout = $maxWait;
    }

    /**
     * @return int
     */
    public function getWaitTimeout(): int
    {
        return $this->waitTimeout;
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
