<?php

namespace Monyxie\Wethook\Driver;

interface EventInterface
{
    /**
     * @return string The identifier of the driver.
     */
    public function getDriver(): string;

    /**
     * @return string The name of the event.
     */
    public function getEvent(): string;

    /**
     * @return string The absolute, canonical uri of the event's target resource.
     */
    public function getTarget(): string;

    /**
     * @return array Any additional data the event may carry.
     */
    public function getData(): array;
}