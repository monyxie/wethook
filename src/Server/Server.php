<?php

namespace Monyxie\Webhooked\Server;

use Psr\Http\Message\ServerRequestInterface;
use Monyxie\Webhooked\Config\ConfigFactory;
use Monyxie\Webhooked\Logger\LoggerFactory;
use React\ChildProcess\Process;
use React\EventLoop\Factory;
use React\Http\Response;
use React\Http\Server as HttpServer;
use React\Socket\Server as SocketServer;

/**
 * Class Server
 *
 * @package Monyxie\Webhooked\Server
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
            do {
                $requestData = json_decode($request->getBody());

                if (! $requestData) {
                    $errorMessage = '请求主体不是有效的JSON';
                    break;
                }

                $requiredFields = [
                    'password',
                    'hook_name',
                    'project'
                ];
                foreach ($requiredFields as $field) {
                    if (! isset($requestData->$field)) {
                        $errorMessage = '请求缺少字段:' . $field;
                        break 2;
                    }
                }

                if ($requestData->password !== $this->config->get('password')) {
                    $errorMessage = '请求密码无效:' . $requestData->password;
                    break;
                }

                if ($requestData->hook_name === 'push_hooks') {
                    $errorMessage = $this->handleRepoName($requestData->project->name_with_namespace);
                    break;
                }
                else {
                    $errorMessage = '无法处理的钩子类型:' . $requestData->hook_name;
                    break;
                }
            } while (false);
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
        }

        if (! empty($errorMessage)) {
            $this->logger->write($errorMessage);
            return 'FAIL';
        }

        return 'OK';
    }

    /**
     * 处理仓库名
     *
     * @param $repoName
     *
     * @return null|string 错误信息
     */
    private function handleRepoName($repoName) {
        $repos = $this->config->get('repos');

        $matched = false;
        foreach ($repos as $repo) {
            if ($repo['name'] === $repoName) {
                $this->runCommands($repo['commands'], $repo['path']);
                $matched = true;
            }
        }

        return $matched ? null : '没有匹配的仓库:' . $repoName;
    }

    /**
     * @param $cmds
     * @param $cwd
     */
    private function runCommands($cmds, $cwd) {

        $resume = null;
        $generatorMaker = function() use ($cmds, $cwd, &$resume) {
            foreach ($cmds as $cmd) {
                yield $this->runCommand($cmd, $cwd, $resume);
            }
        };

        $generator = $generatorMaker();
        $resume = function() use ($generator) {
            $generator->next();
        };

        $generator->current();
    }

    /**
     * 执行命令
     *
     * @param $cwd
     */
    private function runCommand($cmd, $cwd, $onExit) {
        $output        = '';
        $appendOutput  = function($chunk) use (&$output) {
            $output .= $chunk;
        };
        $writeLog      = function() use ($cwd, $cmd, $onExit, &$output) {
            $this->logger->write("[$cwd] ($cmd) $output");
            call_user_func($onExit);
        };

        $process = new Process($cmd, $cwd);
        $process->start($this->loop);
        $process->stdout->on('data', $appendOutput);
        $process->stderr->on('data', $appendOutput);
        $process->on('exit', $writeLog);
    }

}