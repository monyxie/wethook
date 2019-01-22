<?php

use function DI\create;
use function DI\get;
use Monolog\Logger;
use Monyxie\Webhooked\Driver\GiteaDriver;
use Monyxie\Webhooked\Driver\GiteeDriver;
use Monyxie\Webhooked\Driver\Registry;
use Monyxie\Webhooked\Http\LoggingMiddleware;
use Monyxie\Webhooked\Http\Router;
use Monyxie\Webhooked\Task\Factory as TaskFactory;
use Psr\Log\LoggerInterface;
use React\EventLoop\Factory as LoopFactory;
use React\EventLoop\LoopInterface;
use React\Http\Server as HttpServer;
use React\Socket\Server as SocketServer;


return [
    'listen' => '127.0.0.1:7007',
    'gitea.secret' => '733tD00d',
    'gitee.password' => 'P455w0rd',
    'tasks' => [],

    LoopInterface::class => LoopFactory::create(),
    LoggerInterface::class => create(Logger::class)
        ->constructor('logger'),
    SocketServer::class => create(SocketServer::class)
        ->constructor(get('listen'), get(LoopInterface::class)),
    HttpServer::class => create(HttpServer::class)
        ->constructor([get(LoggingMiddleware::class), get(Router::class)]),
    TaskFactory::class => create(TaskFactory::class)
        ->constructor(get('tasks')),
    Registry::class => create(Registry::class)
        ->constructor(get(LoggerInterface::class))
        ->method('addDriver', get(GiteaDriver::class))
        ->method('addDriver', get(GiteeDriver::class))
        ->method('addRoutes', get(Router::class)),
    GiteaDriver::class => create(GiteaDriver::class)
        ->constructor(get('gitea.secret')),
    GiteeDriver::class => create(GiteeDriver::class)
        ->constructor(get('gitee.password')),
];
