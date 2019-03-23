<?php

namespace Monyxie\Wethook\Driver;

use Evenement\EventEmitterInterface;
use Evenement\EventEmitterTrait;
use Monyxie\Wethook\Driver\Exception\DriverException;
use Monyxie\Wethook\Http\Router;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use function RingCentral\Psr7\stream_for;

class Registry implements EventEmitterInterface, \IteratorAggregate
{
    use EventEmitterTrait;

    /**
     * @var DriverInterface[]
     */
    private $endpoints = [];
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
     * @param string $endpoint
     * @param array $config
     * @throws DriverException
     */
    public function addEndpoint(string $endpoint, array $config): void
    {
        if (isset($this->endpoints[$endpoint])) {
            throw new DriverException("Endpoint identifier conflict: " . $endpoint);
        }

        if (empty($config['driver'])) {
            throw new DriverException("Invalid endpoint config.");
        }

        if (!class_exists($config['driver'])) {
            throw new DriverException("Driver not found: " . $config['driver']);
        }

        if (!is_subclass_of($config['driver'], DriverInterface::class)) {
            throw new DriverException("Driver must be an instance of DriverInterface: " . $config['driver']);
        }

        $driver = new $config['driver']($endpoint, $config);

        $this->endpoints[$endpoint] = $driver;
    }

    /**
     * Add routes to a HTTP router according to registered drivers.
     * @param Router $router
     */
    public function registerRoutes(Router $router)
    {
        foreach ($this->endpoints as $endpoint => $driver) {
            $router->addRoute('*', '/' . $endpoint, function (Request $request, Response $response) use ($driver) {
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
        return new \ArrayIterator($this->endpoints);
    }
}