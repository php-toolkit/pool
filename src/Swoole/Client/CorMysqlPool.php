<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-09-08
 * Time: 15:11
 */

namespace Inhere\Pool\Swoole\Client;

use Inhere\Pool\Swoole\CorSuspendPool;
use Swoole\Coroutine\MySQL;

/**
 * Class CorMysqlPool
 * @package Inhere\Pool\Swoole\Client
 */
class CorMysqlPool extends CorSuspendPool
{
    /**
     * @var array
     */
    protected $options = [
        'db1' => [
            'host' => 'mysql',
            'port' => 3306,
            'user' => 'root',
            'password' => 'password',
            'database' => 'my_test',
        ]
    ];

    /**
     * 创建新的资源实例
     * @return mixed
     */
    public function create()
    {
        $conf = $this->options['db1'];
        $db = new MySQL();

//        debug('coId:' . Coroutine::id() . ' will create new db connection');

        $db->connect($conf);

//        debug('coId:' . Coroutine::id() . ' a new db connection created');

        return $db;
    }

    /**
     * 销毁资源实例
     * @param $resource
     * @return void
     */
    public function destroy($resource)
    {
//        unset($resource);
    }
}

