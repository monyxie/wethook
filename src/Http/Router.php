<?php

namespace Monyxie\Wethook\Http;


use Psr\Http\Message\ServerRequestInterface;
use React\Http\Response;

/**
 * Route HTTP requests.
 * @package Monyxie\Wethook\Http
 */
class Router
{
    /**
     * @var array
     */
    private $routes = [];

    /**
     * @param string $method
     * @param string $path
     * @param callable $handler
     */
    public function addRoute(string $method, string $path, callable $handler)
    {
        $this->routes[$path][strtoupper($method)] = $handler;
    }

    /**
     * @param ServerRequestInterface $request
     * @return mixed|Response
     */
    public function __invoke(ServerRequestInterface $request)
    {

        $path = $request->getUri()->getPath();
        $method = strtoupper($request->getMethod());

        if (isset($this->routes[$path])) {
            if (isset($this->routes[$path][$method])) {
                $handler = $this->routes[$path][$method];
            } else if (isset($this->routes[$path]['*'])) {
                $handler = $this->routes[$path]['*'];
            }

            if (isset($handler)) {
                return call_user_func($handler, $request, new Response(200, [], 'OK'));
            }

            return new Response(405, [], '405 Method Not Allowed');
        }

        return new Response(404, [], '404 Not Found');
    }
}