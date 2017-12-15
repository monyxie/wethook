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
        $this->config = ConfigFactory::create();
        $this->logger = LoggerFactory::create();

        $this->loop = Factory::create();
        $this->run();
        $this->loop->run();
    }

    /**
     *
     */
    public function run() {
        $server = new HttpServer(function (ServerRequestInterface $request) {
            $body = $this->handleRequest($request);
            return new Response(200, [], $body );
        });

        $listen = $this->config->get('listen');
        $socket = new SocketServer($listen, $this->loop);
        $server->listen($socket);

        $this->logger->write("Server running at http://{$listen}");
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
                throw new \Exception('请求主体不是有效的JSON');
            }

            if ($requestData->password !== $this->config->get('password')) {
                throw new \Exception('请求密码无效');
            }

            if ($requestData->hook_name === 'push_hooks') {
                $this->handleRepoName($requestData->project->name_with_namespace);
            }

            return 'OK';
        } catch (\Exception $e) {
            $this->logger->write($e->getMessage());
            return 'FAIL';
        }
    }

    /**
     * 处理仓库名
     * @param $repoName
     */
    private function handleRepoName($repoName) {
        $repos = $this->config->get('repos');
        foreach ($repos as $repo) {
            if ($repo['fullname'] === $repoName) {
                $this->pull($repo['path']);
            }
        }
    }

    /**
     * 拉取仓库
     *
     * @param $pathToRepo
     */
    private function pull($pathToRepo) {

        $gitCmdEscaped = escapeshellarg($this->config->get('gitcmd'));
        $pathEscaped   = escapeshellarg($pathToRepo);
        $cmd           = "$gitCmdEscaped -C $pathEscaped pull";
        $output        = '';
        $appendOutput  = function($chunk) use (&$output) {
            $output .= $chunk;
        };
        $writeLog      = function() use ($pathToRepo, &$output) {
            $this->logger->write("$pathToRepo : $output");
        };

        $process = new Process($cmd);
        $process->start($this->loop);
        $process->stdout->on('data', $appendOutput);
        $process->stderr->on('data', $appendOutput);
        $process->on('exit', $writeLog);
    }

}