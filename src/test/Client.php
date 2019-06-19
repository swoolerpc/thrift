<?php

include '../../vendor/autoload.php';

include '/Users/ren/Desktop/html/swoole/phaso/src/Services/HelloSwoole/HelloSwoole.php';
include '/Users/ren/Desktop/html/swoole/phaso/src/Services/HelloSwoole/Types.php';
include '/Users/ren/Desktop/html/swoole/phaso/src/Services/HelloSwoole/Handler.php';


// Transport
$socket = new \Thrift\Transport\TSocket('127.0.0.1', 9998);

$transport = new \Thrift\Transport\TFramedTransport($socket);
// Protocol
$protocol = new \Thrift\Protocol\TBinaryProtocol($transport);

$transport->open();

$start = microtime(true);
//for ($i = 0; $i <= 100; $i++) {
//	go(function () use ($protocol) {
		$tMultiplexedProtocol = new \Thrift\Protocol\TMultiplexedProtocol($protocol, "Services\\HelloSwoole");

		$client = new \Services\HelloSwoole\HelloSwooleClient($tMultiplexedProtocol);

		$msg           = new \Services\HelloSwoole\Message();
		$msg->send_uid = 123;
		$msg->name     = '测试';
		$msg->count    = 100;
		$msg->price    = 600;

		$res = $client->sendMessage($msg);
		var_dump($res);

//		co::sleep(0.5);
//	});
//}

echo microtime(true) - $start;
//$transport->close();