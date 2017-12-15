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

class Server {

    /**
     * @var \React\EventLoop\ExtEventLoop|\React\EventLoop\LibEventLoop|\React\EventLoop\LibEvLoop|\React\EventLoop\StreamSelectLoop
     */
    private $loop;

    function __construct() {
        $this->config = ConfigFactory::create();
        $this->logger = LoggerFactory::create();

        $this->loop = Factory::create();
        $this->run();
        $this->loop->run();
    }

    public function run() {
        $server = new HttpServer(function (ServerRequestInterface $request) {
            $this->handleRequest($request);
            return new Response(
                200,
                array('Content-Type' => 'application/json'),
                ''
            );
        });

        $listen = $this->config->get('listen');
        $socket = new SocketServer($listen, $this->loop);
        $server->listen($socket);

        $this->logger->write("Server running at http://{$listen}");
    }

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
        } catch (\Exception $e) {
            $this->logger->write($e->getMessage());
        }
    }

    private function handleRepoName($repoName) {
        $repos = $this->config->get('repos');
        foreach ($repos as $repo) {
            if ($repo['fullname'] === $repoName) {
                $this->pull($repo['path']);
            }
        }
    }

    private function pull($path) {
        $gitCmdEscaped      = escapeshellarg($this->config->get('gitcmd'));
        $pathEscaped = escapeshellarg($path);
        $cmd         = "$gitCmdEscaped -C $pathEscaped pull";

        $process = new Process($cmd);
        $process->start($this->loop);

        $output = '';

        $process->stdout->on('data', function($chunk) use (&$output) {
            $output .= $chunk;
        });
        $process->stderr->on('data', function($chunk) use (&$output) {
            $output .= $chunk;
        });

        $process->on('exit', function() use ($path, &$output) {
            $this->logger->write("$path : $output");
        });
    }

}