<?php

namespace Monyxie\Wethook\Http;

use Monyxie\Wethook\Driver\Event;
use Monyxie\Wethook\Driver\Registry;
use Monyxie\Wethook\Task\Factory;
use Monyxie\Wethook\Task\Runner;
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
     * @var Runner
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
     * Server constructor.
     * @param LoggerInterface $logger
     * @param HttpServer $httpServer
     * @param SocketServer $socketServer
     * @param Registry $registry
     * @param Factory $factory
     * @param Runner $taskRunner
     * @param Router $router
     * @param WebUi $webUi
     */
    public function __construct(
        LoggerInterface $logger,
        HttpServer $httpServer,
        SocketServer $socketServer,
        Registry $registry,
        Factory $factory,
        Runner $taskRunner,
        Router $router,
        WebUi $webUi
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
    }

    /**
     * Run the server.
     */
    public function run()
    {
        $this->webUi->addRoutes($this->router);
        $this->registry->addRoutes($this->router);
        $this->registry->on('hook', function (Event $hookEvent) {
            if ($tasks = $this->taskFactory->fromHookEvent($hookEvent)) {
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