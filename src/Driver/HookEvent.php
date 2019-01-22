<?php

namespace Monyxie\Webhooked\Driver;

class HookEvent
{
    /**
     * @var string The identifier of the driver.
     */
    public $driver = '';

    /**
     * @var string The name of the event.
     */
    public $event = '';

    /**
     * @var string The name of the resource that triggered the event.
     */
    public $target = '';

    /**
     * @var mixed Any additional data the event may carry.
     */
    public $data = null;

}