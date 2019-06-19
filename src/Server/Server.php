<?php
/**
 * Created by PhpStorm.
 * User: ren
 * Date: 2019/6/15
 * Time: 3:44 PM
 */

namespace SwooleRPC\Server;

use Thrift\TMultiplexedProcessor;

class Server
{
	/**
	 * @var string
	 */
	protected $ip = '127.0.0.1';

	/**
	 * @var int
	 */
	protected $port = 9998;

	/**
	 * Server constructor.
	 * @param array $config 项目配置
	 */
	public function __construct(array $config)
	{
		if (empty($config)) {
			return;
		}
	}

	/**
	 * workerStart
	 */
	public function onStart()
	{
		// 重启opcache
		if (function_exists('opcache_reset')) {
			opcache_reset();
		}
		echo '服务启动成功，ip:', $this->ip, ' port:', $this->port, PHP_EOL;
	}

	/**
	 * 接收数据
	 *
	 * @param \swoole_server $server
	 * @param int $fd
	 * @param int $from_id
	 * @param string $data
	 */
	public function onReceive(\swoole_server $server, int $fd, int $from_id, string $data)
	{
		try {
			$servicesNamespace = $this->getServicesNamespace($server, $fd, $data);

			$arr         = explode('\\', $servicesNamespace);
			$serviceName = $arr[count($arr) - 1];
			unset($arr);

			$handlerClass   = '\\app\\' . $servicesNamespace . '\\' . $serviceName . 'Handler';
			$processorClass = '\\' . $servicesNamespace . '\\' . $serviceName . 'Processor';
			$handler        = new $handlerClass();
			$processor      = new $processorClass($handler);

			$protocol        = $this->getProtocol($server, $fd, $data);
			$protocol->fname = $serviceName;

			$tMultiplexedProcessor = new TMultiplexedProcessor();
			$tMultiplexedProcessor->registerProcessor($servicesNamespace, $processor);
			$tMultiplexedProcessor->process($protocol, $protocol);
		} catch (\Exception $e) {
			echo "error:", $e->getTraceAsString();
		}
	}

	public function onClose(\swoole_server $server, int $fd)
	{

	}

	/**
	 * 启动服务
	 * 配置swoole启动参数
	 *
	 * @param array $swooleConf
	 */
	public function start(array $swooleConf)
	{
		if (empty($swooleConf)) {
			$swooleConf = [
				'log_file'                 => "/tmp/swoole.log",
				'log_level'                => 5,
				'daemonize'                => false,//是否作为守护进程
				'backlog'                  => 128, //listen backlog
				'open_tcp_nodelay'         => 1,
				'enable_reuse_port'        => true,
				'heartbeat_idle_time'      => 180,
				'worker_num'               => 2,
				'dispatch_mode'            => 1,
				'open_length_check'        => false, //打开包长检测
				'package_max_length'       => 8192000, //最大的请求包长度,8M
				'package_length_type'      => 'N', //长度的类型，参见PHP的pack函数
				'package_length_offset'    => 0, //第N个字节是包长度的值
				'package_body_offset'      => 4, //从第几个字节计算长度
				'heartbeat_check_interval' => 10, ///每隔多少秒检测一次，单位秒
			];
		}

		$serv = new \swoole_server($this->ip, $this->port);
		$serv->on('workerStart', [$this, 'onStart']);
		$serv->on('receive', [$this, 'onReceive']);
		$serv->on('close', [$this, 'onClose']);

		$serv->set($swooleConf);

		$serv->start();
	}

	/**
	 * 获取数据传输协议
	 *
	 * @param \swoole_server $server
	 * @param int $fd
	 * @param int $from_id
	 * @param string $data
	 * @return \Thrift\Protocol\TBinaryProtocol
	 */
	private function getProtocol(\swoole_server $server, int $fd, string $data)
	{
		$socket = new Socket();
		$socket->setHandle($fd);
		$socket->buffer = $data;
		$socket->server = $server;

		$protocol = new \Thrift\Protocol\TBinaryProtocol($socket, false, false);

		return $protocol;
	}

	/**
	 * 获取命名空间
	 *
	 * @param \swoole_server $server
	 * @param int $fd
	 * @param string $data
	 * @return string
	 */
	private function getServicesNamespace(\swoole_server $server, int $fd, string $data): string
	{
		$protocol = $this->getProtocol($server, $fd, $data);
		$rseqid   = 0;
		$fname    = null;
		$mtype    = 0;
		$protocol->readMessageBegin($fname, $mtype, $rseqid);
		list($service, $method) = explode(':', $fname);

		return $service;
	}
}