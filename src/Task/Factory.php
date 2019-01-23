<?php

namespace Monyxie\Wethook\Task;

use Monyxie\Wethook\Driver\HookEvent;

/**
 * Task factory.
 * @package Monyxie\Wethook\Http
 */
class Factory
{
    /**
     * @var array
     */
    private $tasks;

    /**
     * Factory constructor.
     * @param $taskDefinitions
     */
    public function __construct($taskDefinitions)
    {
        $this->tasks = $taskDefinitions;
    }

    /**
     * @param HookEvent $hookEvent
     * @return Task[]
     */
    public function fromHookEvent(HookEvent $hookEvent)
    {
        $tasks = [];

        $env = [
            'wh.driver' => $hookEvent->driver,
            'wh.event' => $hookEvent->event,
            'wh.target' => $hookEvent->target,
            'wh.data' => $hookEvent->data,
        ];

        foreach ($this->tasks as $item) {
            if ($this->matchDefinition($hookEvent, $item)) {
                foreach ($item['where'] as $dir) {
                    foreach ($item['what'] as $cmd) {
                        $tasks [] = new Task($cmd, $dir, $env);
                    }
                }
            }
        }

        return $tasks;
    }

    /**
     * @param HookEvent $hookEvent
     * @param $definition
     * @return bool
     */
    private function matchDefinition(HookEvent $hookEvent, $definition): bool
    {
        if (isset($definition['when']['driver'])) {
            if ($hookEvent->driver !== $definition['when']['driver']) return false;
        }
        if (isset($definition['when']['event'])) {
            if ($hookEvent->event !== $definition['when']['event']) return false;
        }
        if (isset($definition['when']['target'])) {
            if ($hookEvent->target !== $definition['when']['target']) return false;
        }

        return true;
    }
}