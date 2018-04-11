<?php
/**
 * Created by PhpStorm.
 * User: monyxie
 * Date: 18-3-28
 * Time: 下午10:41
 */

namespace Monyxie\Webhooked\Server;


use Psr\Http\Message\ServerRequestInterface;
use React\Http\Response;

/**
 * Class Router
 * @package Monyxie\Webhooked\Server
 */
class Router {
    /**
     * @var array
     */
    private $routes = [];

    /**
     * @param string $method
     * @param string $path
     * @param callable $handler
     */
    public function register(string $method, string $path, callable $handler) {
        $this->routes[$path][strtoupper($method)] = $handler;
    }

    /**
     * @param ServerRequestInterface $request
     * @return mixed|Response
     */
    public function route(ServerRequestInterface $request) {

        $path = $request->getUri()->getPath();
        $method = strtoupper($request->getMethod());

        if (isset($this->routes[$path][$method])) {
            return call_user_func($this->routes[$path][$method], $request);
        }
        else if (isset($this->routes[$path])) {
            return new Response(405, [], '405 Method Not Allowed');
        }

        return new Response(404, [], '404 Not Found');
    }
}