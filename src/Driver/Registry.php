<?php

namespace Monyxie\Webhooked\Driver;

use Evenement\EventEmitterInterface;
use Evenement\EventEmitterTrait;
use Monyxie\Webhooked\Driver\Exception\DriverException;
use Monyxie\Webhooked\Http\Router;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use function RingCentral\Psr7\stream_for;

class Registry implements EventEmitterInterface, \IteratorAggregate
{
    use EventEmitterTrait;

    /**
     * @var DriverInterface[]
     */
    private $drivers = [];
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

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

    /**
     * Add routes to a HTTP router according to registered drivers.
     * @param Router $router
     */
    public function addRoutes(Router $router) {
        foreach ($this->drivers as $identifier => $driver) {
            $router->addRoute('*', '/' . $identifier, function (ServerRequestInterface $request, ResponseInterface $response) use ($identifier, $driver) {
                try {
                    $result = $driver->handle($request, $response);
                } catch (DriverException $e) {
                    $this->logger->notice('Exception when calling driver handle method.', ['exception' => $e]);
                    return $response->withBody(stream_for('FAIL'));
                }

                $this->emit('hook', [$result->getEvent()]);
                return $result->getResponse();
            });
        }
    }

    /**
     * @inheritdoc
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->drivers);
    }
}