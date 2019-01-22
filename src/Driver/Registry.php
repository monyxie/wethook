<?php

namespace Monyxie\Webhooked\Driver;

use Evenement\EventEmitterInterface;
use Evenement\EventEmitterTrait;
use Monyxie\Webhooked\Driver\Exception\DriverException;
use Monyxie\Webhooked\Http\Router;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class Registry implements EventEmitterInterface
{
    use EventEmitterTrait;

    /**
     * @var DriverInterface[]
     */
    private $drivers = [];

    /**
     * Register a driver.
     * @param DriverInterface $driver
     * @throws DriverException
     */
    public function addDriver(DriverInterface $driver): void
    {
        $identifier = $driver->getIdentifier();
        if (isset($this->drivers[$identifier])) {
            throw new DriverException("Driver identifier conflict: " . $identifier);
        }

        $this->drivers[$identifier] = $driver;
    }

    public function addRoutes(Router $router) {
        foreach ($this->drivers as $identifier => $driver) {
            $router->addRoute('*', $identifier, function (ServerRequestInterface $request, ResponseInterface $response) use ($identifier, $driver) {
                $result = $driver->handle($request, $response);
                $this->emit('hook', [$result->getEvent()]);
                return $result->getResponse();
            });
        }
    }
}