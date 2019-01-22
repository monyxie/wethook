<?php

namespace Monyxie\Webhooked\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

/**
 * Log HTTP requests.
 * @package Monyxie\Webhooked\Http
 */
class LoggingMiddleware
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param ServerRequestInterface $request
     * @param Callable $next
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, Callable $next)
    {
        /** @var ResponseInterface $response */
        $response = call_user_func($next, $request);

        $path = $request->getUri()->getPath();
        $method = strtoupper($request->getMethod());
        $status = $response->getStatusCode();

        $this->logger->info("Request handled.", ['status' => $status, 'method' => $method, 'path' => $path]);

        return $response;
    }
}