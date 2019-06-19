<?php
/**
 * Created by PhpStorm.
 * User: ren
 * Date: 2019/6/15
 * Time: 5:58 PM
 */
include '../../vendor/autoload.php';
include '/Users/ren/Desktop/html/swoole/phaso/src/Services/HelloSwoole/HelloSwoole.php';
include '/Users/ren/Desktop/html/swoole/phaso/src/Services/HelloSwoole/Types.php';
include '/Users/ren/Desktop/html/swoole/phaso/src/Services/HelloSwoole/Handler.php';
try {
	$server = new \SwooleRPC\Server\Server(['ip' => '127.0.0.1']);

	$server->start([]);
}catch (\Exception $e) {
	var_dump($e->getTraceAsString());
}catch (\Error $e) {
	var_dump($e->getTraceAsString());
}
