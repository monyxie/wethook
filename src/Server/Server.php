<?php

namespace Puller\Server;

use Psr\Http\Message\ServerRequestInterface;
use Puller\Config\ConfigFactory;
use Puller\Logger\LoggerFactory;
use React\ChildProcess\Process;
use React\EventLoop\Factory;
use React\Http\Response;
use React\Http\Server as HttpServer;
use React\Socket\Server as SocketServer;

/**
 * Class Server
 *
 * @package Puller\Server
 */
class Server {

    /**
     * @var \React\EventLoop\ExtEventLoop|\React\EventLoop\LibEventLoop|\React\EventLoop\LibEvLoop|\React\EventLoop\StreamSelectLoop
     */
    private $loop;

    /**
     * Server constructor.
     */
    function __construct() {
        $this->config = ConfigFactory::get();
        $this->logger = LoggerFactory::get();

        $this->loop = Factory::create();
        $this->run();
        $this->loop->run();
    }

    /**
     *
     */
    public function run() {
        $server = new HttpServer(function (ServerRequestInterface $request) {
            if (strtoupper($request->getMethod()) !== 'POST') {
                return new Response(405, [], '405 Method Not Allowed');
            }

            $this->logger->write('接收到请求');
            $body = $this->handleRequest($request);
            return new Response(200, [], $body );
        });

        $listen = $this->config->get('listen');
        $socket = new SocketServer($listen, $this->loop);
        $server->listen($socket);

        $this->logger->write("服务器正在以下地址上运行： http://{$listen}");
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return string
     */
    public function handleRequest(ServerRequestInterface $request) {
        try {
            $requestData = json_decode($request->getBody());

            if (! $requestData) {
                $errorMessage = '请求主体不是有效的JSON';
            }

            if ($requestData->password !== $this->config->get('password')) {
                $errorMessage = '请求密码无效';
            }

            if ($requestData->hook_name === 'push_hooks') {
                $this->handleRepoName($requestData->project->name_with_namespace);
            }
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
        }

        if (isset($errorMessage)) {
            $this->logger->write($errorMessage);
            return 'FAIL';
        }

        return 'OK';
    }

    /**
     * 处理仓库名
     * @param $repoName
     */
    private function handleRepoName($repoName) {
        $repos = $this->config->get('repos');
        foreach ($repos as $repo) {
            if ($repo['fullname'] === $repoName) {
                foreach ($repo['cmds'] as $cmd) {
                    $this->runCommand($cmd, $repo['path']);
                }
            }
        }
    }

    /**
     * 执行命令
     *
     * @param $cwd
     */
    private function runCommand($cmd, $cwd) {
        $output        = '';
        $appendOutput  = function($chunk) use (&$output) {
            $output .= $chunk;
        };
        $writeLog      = function() use ($cwd, $cmd, &$output) {
            $this->logger->write("[$cwd] ($cmd) $output");
        };

        $process = new Process($cmd, $cwd);
        $process->start($this->loop);
        $process->stdout->on('data', $appendOutput);
        $process->stderr->on('data', $appendOutput);
        $process->on('exit', $writeLog);
    }

}