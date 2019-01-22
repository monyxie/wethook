<?php

namespace Monyxie\Webhooked\Task;

use Monyxie\Webhooked\Driver\HookEvent;

/**
 * Task factory.
 * @package Monyxie\Webhooked\Http
 */
class Factory
{
    /**
     * @var string
     */
    private $hookPath;

    /**
     * Factory constructor.
     * @param $hookPath
     */
    public function __construct($hookPath)
    {
        $this->hookPath = rtrim($hookPath, '/\\') . DIRECTORY_SEPARATOR;
    }

    /**
     * @param HookEvent $hookEvent
     * @return Task
     */
    public function fromHookEvent(HookEvent $hookEvent)
    {
        if (! $hookEvent->driver || ! $hookEvent->event) {
            return null;
        }

        $filename = $this->hookPath . $hookEvent->driver . DIRECTORY_SEPARATOR . $hookEvent->event;

        if (! file_exists($filename)) {
            return null;
        }

        return new Task($filename, '.');
    }

}