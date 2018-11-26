<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/6/24
 * Time: 下午7:16
 */

namespace Toolkit\Pool;

/**
 * Class FactoryInterface - resource factory interface
 * @package Toolkit\Pool
 */
interface FactoryInterface
{
    /**
     * @return mixed
     */
    public function create();

    /**
     * @param \stdClass|mixed $obj The resource
     * @return mixed
     */
    public function destroy($obj);

    /**
     * @param \stdClass|mixed $obj The resource
     * @return bool
     */
    public function validate($obj): bool;
}
