<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/6/24
 * Time: 上午9:31
 */

namespace Toolkit\Pool\Raw;

use Toolkit\Pool\SPLQueuePool;

/**
 * Class ResourcePool - 资源池
 * - 通过设置两个闭包来实现资源的创建和销毁
 *
 * ```php
 * $rpl = new ResourcePool([
 *  'maxSize' => 50,
 * ]);
 *
 * $rpl->setResourceCreator(function () {
 *  return new \Db(...);
 * );
 *
 * $rpl->setResourceDestroyer(function ($db) {
 *   $db->close();
 * );
 *
 * // use
 * $db = $rpl->get();
 *
 * $rows = $db->query('select * from table limit 10');
 *
 * $rpl->put($db);
 * ```
 *
 * @package Toolkit\Pool\Raw
 */
class ResourcePool extends SPLQueuePool
{
    /**
     * 资源创建者
     * @var \Closure
     */
    private $creator;

    /**
     * 资源销毁者
     * @var \Closure
     */
    private $destroyer;

    protected function init()
    {
        parent::init();

        if ($this->creator) {
            $this->prepare($this->getInitSize());
        }
    }

    /**
     * 等待并返回可用资源
     * @return bool|mixed
     */
    protected function wait()
    {
        $timer = 0;
        $timeout = $this->getMaxWait();
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
        $this->destroyer = $this->creator = null;

        parent::clear();
    }

    public function create()
    {
        $cb = $this->creator;

        return $cb();
    }

    public function destroy($resource)
    {
        $cb = $this->destroyer;
        $cb($resource);
    }

    /**
     * @return \Closure
     */
    public function getCreator()
    {
        return $this->creator;
    }

    /**
     * @param \Closure $creator
     * @return $this
     */
    public function setCreator(\Closure $creator)
    {
        $this->creator = $creator;

        // 预准备资源
        $this->prepare($this->getInitSize());

        return $this;
    }

    /**
     * @return \Closure
     */
    public function getDestroyer()
    {
        return $this->destroyer;
    }

    /**
     * @param \Closure $destroyer
     * @return $this
     */
    public function setDestroyer(\Closure $destroyer)
    {
        $this->destroyer = $destroyer;

        return $this;
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
