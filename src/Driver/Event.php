<?php

namespace Monyxie\Wethook\Driver;

class Event implements EventInterface
{
    /**
     * @var string
     */
    protected $driver = '';

    /**
     * @var string
     */
    protected $event = '';

    /**
     * @var string
     */
    protected $target = '';

    /**
     * @var array
     */
    protected $data = [];

    public function __construct(
        string $driver = '',
        string $event = '',
        string $target = '',
        array $data = []
    )
    {
        $this->driver = $driver;
        $this->event = $event;
        $this->target = $target;
        $this->data = $data;
    }

    /**
     * @return string The identifier of the driver.
     */
    public function getDriver(): string
    {
        return $this->driver;
    }

    /**
     * @return string The name of the event.
     */
    public function getEvent(): string
    {
        return $this->event;
    }

    /**
     * @return string The absolute, canonical uri of the event's target resource.
     */
    public function getTarget(): string
    {
        return $this->target;
    }

    /**
     * @return array Any additional data the event may carry.
     */
    public function getData(): array
    {
        return $this->data;
    }
}