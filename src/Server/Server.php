<?php

namespace Monyxie\Webhooked\Server;

use Evenement\EventEmitter;
use Monyxie\Webhooked\Request\Gitea\GiteaRequest;
use Monyxie\Webhooked\Request\Gitee\GiteeRequest;
use Monyxie\Webhooked\Request\MalformedRequestException;
use Psr\Http\Message\ServerRequestInterface;
use Monyxie\Webhooked\Config\ConfigFactory;
use Monyxie\Webhooked\Logger\LoggerFactory;
use React\EventLoop\Factory;
use React\EventLoop\LoopInterface;
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
     * @var LoopInterface
     */
    private $loop;
    /**
     * @var Router
     */
    private $router;
    /**
     * @var Runner
     */
    private $runner;
    /**
     * @var \Monyxie\Webhooked\Config\Config
     */
    private $config;
    /**
     * @var \Monyxie\Webhooked\Logger\LoggerInterface
     */
    private $logger;

    /**
     * Server constructor.
     */
    public function __construct() {
        $that = $this;

        $this->config = ConfigFactory::get();
        $this->logger = LoggerFactory::get();
        $this->loop = Factory::create();

        $this->router = new Router();
        $this->router->register('POST', '/gitea', $this->getHandlerForRequestClass(GiteaRequest::class));
        $this->router->register('POST', '/gitee', $this->getHandlerForRequestClass(GiteeRequest::class));

        $this->runner = new Runner($this->loop, $this->config);
        $this->runner->on(Runner::EVENT_AFTER_COMMAND, function ($command, $cwd, $output) use ($that) {
            $that->logger->write("[{$cwd}] ({$command}) {$output}");
        });
    }

    /**
     *
     */
    public function run() {
        $that = $this;

        $server = new HttpServer([
            function (ServerRequestInterface $request, callable $next) {
                $this->logger->write($request->getMethod() . ' ' . $request->getUri());
                return $next($request);
            },
            function (ServerRequestInterface $request) {
                return $this->router->route($request);
            },
        ]);
        $server->on('error', function ($error) use ($that) {
            $message = $error instanceof \Exception ? $error->getMessage() : var_export($error, true);
            $that->logger->write($message);
        });

        $listenAddress = $this->config->get('listen');
        $server->listen(new SocketServer($listenAddress, $this->loop));

        $this->logger->write("Server started at： http://{$listenAddress}");
        $this->loop->run();
    }

    private function getHandlerForRequestClass(string $requestClass) {
        $that = $this;
        return function (ServerRequestInterface $httpRequest) use ($that, $requestClass) {
            try {
                $request = new $requestClass($httpRequest);
            }
            catch (MalformedRequestException $e) {
                return new Response(400, [], '400 Bad request.');
            }

            $body = $this->runner->run($request);
            return new Response(200, [], $body);
        };
    }
}