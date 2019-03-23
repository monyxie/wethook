<?php

use League\Plates\Engine as TemplateEngine;
use Monolog\Logger;
use Monyxie\Wethook\Driver\GiteaDriver;
use Monyxie\Wethook\Driver\GiteeDriver;
use Monyxie\Wethook\Driver\GithubDriver;
use Monyxie\Wethook\Driver\GitlabDriver;
use Monyxie\Wethook\Driver\Registry;
use Monyxie\Wethook\Http\LoggingMiddleware;
use Monyxie\Wethook\Http\Router;
use Monyxie\Wethook\Task\Factory as TaskFactory;
use Monyxie\Wethook\Task\Runner\AsynchronousRunner;
use Monyxie\Wethook\Task\Runner\RunnerInterface;
use Psr\Log\LoggerInterface;
use React\EventLoop\Factory as LoopFactory;
use React\EventLoop\LoopInterface;
use React\Http\Server as HttpServer;
use React\Socket\Server as SocketServer;
use function DI\autowire;
use function DI\create;
use function DI\decorate;
use function DI\get;


return [
    'listen' => '127.0.0.1:7007',
    'endpoints' => [],
    'tasks' => [],

    'driver_aliases' => [
        'gitea' => GiteaDriver::class,
        'gitee' => GiteeDriver::class,
        'github' => GithubDriver::class,
        'gitlab' => GitlabDriver::class,
    ],

    LoopInterface::class => LoopFactory::create(),
    LoggerInterface::class => create(Logger::class)
        ->constructor('logger'),
    SocketServer::class => create(SocketServer::class)
        ->constructor(get('listen'), get(LoopInterface::class)),
    HttpServer::class => autowire(HttpServer::class)
        ->constructor([get(LoggingMiddleware::class), get(Router::class)]),
    TaskFactory::class => create(TaskFactory::class)
        ->constructor(get('tasks')),
    TemplateEngine::class => create(TemplateEngine::class)
        ->constructor(PATH_ROOT . '/resources/views'),
    RunnerInterface::class => autowire(AsynchronousRunner::class),

    Registry::class => decorate(function (Registry $registry, \Psr\Container\ContainerInterface $container) {
        $endpoints = $container->get('endpoints');
        $aliases = $container->get('driver_aliases');
        foreach ($endpoints as $identifier => $config) {
            if (!isset($config['driver'])) {
                throw new \Exception('Invalid driver configuration.');
            }

            if (isset($aliases[$config['driver']])) {
                $config['driver'] = $aliases[$config['driver']];
            }

            $registry->addEndpoint($identifier, $config);
        }

        return $registry;
    }),
];
