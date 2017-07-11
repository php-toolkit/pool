<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/6/24
 * Time: 下午7:16
 */

namespace inhere\pool;

/**
 * Class ResourceInterface - resource factory interface
 * @package inhere\pool
 */
interface ResourceInterface
{
    public function create();

    public function destroy($obj);
}
