<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2018/2/6 0006
 * Time: 20:35
 */

namespace Inhere\Pool;

use SwooleLib\Pool\Co\MySQL\MySQLPool;

/**
 * Class PoolManager
 * @package Inhere\Pool
 */
class PoolManager
{
    /**
     * @var self
     */
    private static $_instance;

    /**
     * @var PoolInterface[]
     */
    protected $pools = [];

    /**
     * @var array
     */
    protected $configs = [];

    /**
     * @return PoolManager
     */
    public static function instance(): PoolManager
    {
        if (!self::$_instance) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    private function __construct()
    {
    }

    private function __clone()
    {
    }

    public function __destruct()
    {
        foreach ($this->pools as $pool) {
            $pool->clear();
        }
    }

    public function init()
    {
        foreach ($this->configs as $config) {
            $pool = new MySQLPool($config);
            $pool->initPool();

            $this->pools[$pool->getName()] = $pool;
        }
    }

    /**
     * @param string $poolName
     * @return mixed
     */
    public function get(string $poolName)
    {
        if (isset($this->pools[$poolName])) {
            return $this->pools[$poolName]->get();
        }

        return null;
    }

    /**
     * @param string $poolName
     * @param mixed $resource
     */
    public function put(string $poolName, $resource)
    {
        if (isset($this->pools[$poolName])) {
            $this->pools[$poolName]->put($resource);
        }
    }

    public function clear(string $poolName = null)
    {
        if ($poolName && isset($this->pools[$poolName])) {
            $this->pools[$poolName]->clear();
        } else {
            foreach ($this->pools as $pool) {
                $pool->clear();
            }
        }
    }

    /**
     * @param string $poolName
     * @return PoolInterface|null
     */
    public function getPool(string $poolName)
    {
        return $this->pools[$poolName] ?? null;
    }

    /**
     * @param string $poolName
     * @return bool
     */
    public function hasPool(string $poolName): bool
    {
        return isset($this->pools[$poolName]);
    }
}
