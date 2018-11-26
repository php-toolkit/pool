<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/6/25
 * Time: 上午10:53
 */

use Toolkit\Pool\UnlimitedPool;

require dirname(__DIR__) . '/test/boot.php';

class TestObj implements \Toolkit\Pool\FactoryInterface {
	public function create() {
		$obj = new \stdClass();
		$obj->name = 'test';

		return $obj;
	}

	public function destroy($obj) {
		echo "release() method.\n";
	}

	public function validate($obj): bool
    {
        return true;
    }
}

$spl = new UnlimitedPool(new TestObj());

$obj1 = $spl->get();
$obj2 = $spl->get();

var_dump($obj1, $obj2);

$spl->put($obj1);
$spl->put($obj2);

var_dump($spl);
