<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-09-08
 * Time: 10:06
 */

namespace Inhere\Pool\Raw\Raw;

/**
 * Class SingleObjectPool - 只管理一个类的对象池
 * @package Inhere\Pool\Raw\Raw
 */
class SingleObjectPool
{
    /**
     * @var array
     */
    private $instances = [];

    /**
     * @var string
     */
    private $class;

    /**
     * SingleObjectPool constructor.
     * @param $class
     */
    public function __construct($class)
    {
        $this->class = $class;
    }

    public function __destruct()
    {
        $this->class = null;
        $this->instances = [];
    }

    /**
     * @return mixed
     */
    public function get()
    {
        if (count($this->instances) > 0) {
            return array_pop($this->instances);
        }

        $class = $this->class;

        return new $class();
    }

    /**
     * @param $instance
     */
    public function put($instance)
    {
        $this->instances[] = $instance;
    }
}