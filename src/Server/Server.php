<?php

namespace Monyxie\Webhooked\Server;

use Monyxie\Webhooked\Request\Gitea\GiteaRequest;
use Monyxie\Webhooked\Request\Gitee\GiteeRequest;
use Monyxie\Webhooked\Request\MalformedRequestException;
use Monyxie\Webhooked\Server\Task\TaskRunner;
use Monyxie\Webhooked\Server\Task\TaskQueueManager;
use Monyxie\Webhooked\Server\Task\InvalidSecretException;
use Monyxie\Webhooked\Server\Task\RequestTasksMapper;
use Monyxie\Webhooked\Server\Task\UnsupportedEventException;
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
     * @var RequestTasksMapper
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
     * @var TaskQueueManager
     */
    private $manager;

    /**
     * Server constructor.
     */
    public function __construct() {
        $this->config = ConfigFactory::get();
        $this->logger = LoggerFactory::get();
        $this->loop = Factory::create();

        $this->router = new Router();
        $this->router->register('POST', '/gitea', $this->createHandlerForRequestClass(GiteaRequest::class));
        $this->router->register('POST', '/gitee', $this->createHandlerForRequestClass(GiteeRequest::class));

        $this->mapper = new RequestTasksMapper($this->config);

        $this->manager = new TaskQueueManager($this->loop);
//        $this->manager->on(TaskQueueManager::EVENT_AFTER_ENQUEUE, function ($command, $cwd) {
//            $command = var_export($command, true);
//            $this->logger->write("<{$cwd}> {$command}");
//        });
//        $this->manager->on(TaskRunner::EVENT_BEFORE_RUN, function ($command, $cwd) {
//            $command = var_export($command, true);
//            $this->logger->write("<{$cwd}> {$command}");
//        });
        $this->manager->on(TaskRunner::EVENT_AFTER_RUN, function ($command, $cwd, $output) {
            $cwd = var_export($cwd, true);
            $command = var_export($command, true);
            if ($output !== '') {
                $output = ' > ' . join("\n > ", explode("\n", rtrim($output, "\n")));
            }
            $this->logger->write("Finished running ({$command}, {$cwd})\n{$output}");
        });
    }

    /**
     *
     */
    public function run() {
        $server = new HttpServer([
            function (ServerRequestInterface $request, callable $next) {
                $this->logger->write($request->getMethod() . ' ' . $request->getUri());
                return $next($request);
            },
            function (ServerRequestInterface $request) {
                $this->loop->addTimer(0, function() {
                    $numRunning = $this->manager->getNumRunning();
                    $numQueueing = $this->manager->getNumQueueing();
                    $this->logger->write("Queueing tasks: $numQueueing, Running tasks: $numRunning");
                });
                return $this->router->route($request);
            },
        ]);
        $server->on('error', function ($error) {
            $message = $error instanceof \Exception ? $error->getMessage() : var_export($error, true);
            $this->logger->write($message);
        });

        $listenAddress = $this->config->get('listen');
        $server->listen(new SocketServer($listenAddress, $this->loop));

        $this->logger->write("Server started at http://{$listenAddress}");
        $this->loop->run();
    }

    private function createHandlerForRequestClass(string $requestClass) {
        return function (ServerRequestInterface $httpRequest) use ($requestClass) {
            try {
                $request = new $requestClass($httpRequest);
            }
            catch (MalformedRequestException $e) {
                return new Response(400, [], json_encode(['info' => 'bad request']));
            }

            try {
                $tasks = $this->mapper->map($request);
            }
            catch (InvalidSecretException $e) {
                return new Response(400, [], json_encode(['info' => 'invalid secret']));
            }
            catch (UnsupportedEventException $e) {
                return new Response(400, [], json_encode(['info' => 'unsupported event']));
            }

            if (! $tasks) {
                return new Response(200, [], json_encode(['info' => 'ok']));
            }

            foreach ($tasks as $task) {
                $this->manager->enqueue($task);
            }

            return new Response(200, [], json_encode(['info' => 'ok']));
        };
    }
}