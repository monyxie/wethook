<?php

namespace Monyxie\Wethook;

use Monyxie\Wethook\Driver\Event;
use Monyxie\Wethook\Driver\Registry;
use Monyxie\Wethook\Http\Router;
use Monyxie\Wethook\Http\WebUi;
use Monyxie\Wethook\Task\Factory;
use Monyxie\Wethook\Task\RunnerInterface;
use Psr\Log\LoggerInterface;
use React\Http\Server as HttpServer;
use React\Socket\Server as SocketServer;

/**
 * Class Server
 *
 * @package Monyxie\Wethook\Http
 */
class Server
{
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var HttpServer
     */
    private $httpServer;
    /**
     * @var Registry
     */
    private $registry;
    /**
     * @var RunnerInterface
     */
    private $taskRunner;
    /**
     * @var Factory
     */
    private $taskFactory;
    /**
     * @var SocketServer
     */
    private $socketServer;
    /**
     * @var Router
     */
    private $router;
    /**
     * @var WebUi
     */
    private $webUi;
    /**
     * @var Monitor
     */
    private $monitor;

    /**
     * Server constructor.
     * @param LoggerInterface $logger
     * @param HttpServer $httpServer
     * @param SocketServer $socketServer
     * @param Registry $registry
     * @param Factory $factory
     * @param RunnerInterface $taskRunner
     * @param Router $router
     * @param WebUi $webUi
     * @param Monitor $monitor
     */
    public function __construct(
        LoggerInterface $logger,
        HttpServer $httpServer,
        SocketServer $socketServer,
        Registry $registry,
        Factory $factory,
        RunnerInterface $taskRunner,
        Router $router,
        WebUi $webUi,
        Monitor $monitor
    )
    {
        $this->logger = $logger;
        $this->httpServer = $httpServer;
        $this->registry = $registry;
        $this->taskRunner = $taskRunner;
        $this->taskFactory = $factory;
        $this->socketServer = $socketServer;
        $this->router = $router;
        $this->webUi = $webUi;
        $this->monitor = $monitor;
    }

    /**
     * Run the server.
     */
    public function run()
    {
        $this->monitor->monitorRegistry($this->registry);
        $this->monitor->monitorRunner($this->taskRunner);
        $this->webUi->addRoutes($this->router);
        $this->registry->addRoutes($this->router);
        $this->registry->on('hook', function (Event $hookEvent) {
            if ($tasks = $this->taskFactory->fromDriverEvent($hookEvent)) {
                foreach ($tasks as $task) {
                    $this->taskRunner->enqueue($task);
                }
            }
        });

        $this->httpServer->on('error', function ($error) {
            $message = $error instanceof \Exception ? $error->getMessage() : var_export($error, true);
            $this->logger->error($message, (array)$error);
        });

        $this->httpServer->listen($this->socketServer);

        $this->logger->info("Server started.", ['address' => $this->socketServer->getAddress()]);
    }
}