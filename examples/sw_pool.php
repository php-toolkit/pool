<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-09-08
 * Time: 14:48
 */
use Inhere\Pool\Swoole\CoroSuspendPool;
use Swoole\Http\Response;

require __DIR__ . '/s-autoload.php';


class MysqlPoolTest extends CoroSuspendPool
{
    /**
     * 创建新的资源实例
     * @return mixed
     */
    public function create()
    {
        $db = new Swoole\Coroutine\MySQL();
        $db->connect([
            'host' => 'mysql',
            'port' => 3306,
            'user' => 'root',
            'password' => 'password',
            'database' => 'test',
        ]);

        debug('create new db connection');

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

$pool = new \MysqlPoolTest([
    'initSize' => 0,
    'maxSize' => 1,
]);

$host = '127.0.0.1';
$port = 8399;
$svr = new \Swoole\Http\Server($host, $port);

echo "server run on {$host}:{$port}\n";

$svr->on('request', function ($req, Response $res) use($pool) {
    $db = $pool->get();

    $data = $db->query('show tables');

    var_dump($data);

    $res->end("hello world!\n");
});

$svr->set([

]);
$svr->start();