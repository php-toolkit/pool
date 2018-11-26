<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-09-08
 * Time: 10:06
 */

namespace Toolkit\Pool\Raw\Raw;

/**
 * Class SingleObjectPool - 只管理一个类的对象池
 * @package Toolkit\Pool\Raw\Raw
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
     * @var object
     */
    private $basicObject;

    /**
     * SingleObjectPool constructor.
     * @param $class
     */
    public function __construct($class)
    {
        $this->class = $class;
        $this->basicObject = new $class;
    }

    public function __destruct()
    {
        $this->class = null;
        $this->basicObject = null;
        $this->instances = [];
    }

    /**
     * @return mixed
     */
    public function get()
    {
        if (\count($this->instances) > 0) {
            return array_pop($this->instances);
        }

        return clone $this->basicObject;
    }

    /**
     * @param $instance
     */
    public function put($instance)
    {
        $this->instances[] = $instance;
    }
}
