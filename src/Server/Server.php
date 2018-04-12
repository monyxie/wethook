<?php

namespace Monyxie\Webhooked\Server;

use Evenement\EventEmitter;
use Monyxie\Webhooked\Request\Gitea\GiteaRequest;
use Monyxie\Webhooked\Request\Gitee\GiteeRequest;
use Monyxie\Webhooked\Request\MalformedRequestException;
use Monyxie\Webhooked\Server\Command\CommandExecutor;
use Monyxie\Webhooked\Server\Command\CommandQueueManager;
use Monyxie\Webhooked\Server\Command\InvalidSecretException;
use Monyxie\Webhooked\Server\Command\RequestCommandsMapper;
use Monyxie\Webhooked\Server\Command\UnsupportedEventException;
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
     * @var RequestCommandsMapper
     */
    private $mapper;
    /**
     * @var \Monyxie\Webhooked\Config\Config
     */
    private $config;
    /**
     * @var \Monyxie\Webhooked\Logger\LoggerInterface
     */
    private $logger;
    /**
     * @var CommandQueueManager
     */
    private $manager;

    /**
     * Server constructor.
     */
    public function __construct() {
        $that = $this;

        $this->config = ConfigFactory::get();
        $this->logger = LoggerFactory::get();
        $this->loop = Factory::create();

        $this->router = new Router();
        $this->router->register('POST', '/gitea', $this->createHandlerForRequestClass(GiteaRequest::class));
        $this->router->register('POST', '/gitee', $this->createHandlerForRequestClass(GiteeRequest::class));

        $this->mapper = new RequestCommandsMapper($this->config);

        $this->manager = new CommandQueueManager($this->loop);
        $this->manager->on(CommandExecutor::EVENT_AFTER_COMMAND, function ($command, $cwd, $output) use ($that) {
            $command = var_export($command, true);
            $that->logger->write("<{$cwd}> {$command} {$output}");
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

        $this->logger->write("Server started at http://{$listenAddress}");
        $this->loop->run();
    }

    private function createHandlerForRequestClass(string $requestClass) {
        $that = $this;
        return function (ServerRequestInterface $httpRequest) use ($that, $requestClass) {
            try {
                $request = new $requestClass($httpRequest);
            }
            catch (MalformedRequestException $e) {
                return new Response(400, [], '400 Bad request.');
            }

            try {
                $commands = $this->mapper->map($request);
            }
            catch (InvalidSecretException $e) {
                return new Response(400, [], 'Invalid secret');
            }
            catch (UnsupportedEventException $e) {
                return new Response(400, [], 'Unsupported event');
            }

            if (! $commands) {
                return new Response(200, [], 'OK, but there\'s no commands defined for this repo');
            }

            foreach ($commands as $command) {
                $this->manager->enqueue($command);
            }

            return new Response(200, [], 'OK, ' . count($commands) . ' command(s) queued');
        };
    }
}