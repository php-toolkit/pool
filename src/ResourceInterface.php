<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/6/24
 * Time: 下午7:16
 */

namespace Inhere\Pool;

/**
 * Class ResourceInterface - resource factory interface
 * @package Inhere\Pool
 */
interface ResourceInterface
{
    /**
     * @return mixed
     */
    public function create();

    /**
     * @param \stdClass $obj the resource object
     * @return mixed
     */
    public function destroy($obj);
}
