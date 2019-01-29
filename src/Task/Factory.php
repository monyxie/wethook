<?php

namespace Monyxie\Wethook\Task;

use Monyxie\Wethook\Driver\Event;
use Monyxie\Wethook\Driver\EventInterface;

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
     * @param Event $event
     * @return Task[]
     */
    public function fromDriverEvent(Event $event)
    {
        $tasks = [];

        $env = [
            'wh.driver' => $event->getDriver(),
            'wh.event' => $event->getEvent(),
            'wh.target' => $event->getTarget(),
            'wh.data' => $event->getData(),
        ];

        foreach ($this->tasks as $item) {
            if ($this->matchDefinition($event, $item)) {
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
     * @param EventInterface $hookEvent
     * @param $definition
     * @return bool
     */
    private function matchDefinition(EventInterface $hookEvent, $definition): bool
    {
        if (isset($definition['when']['driver'])) {
            if ($hookEvent->getDriver() !== $definition['when']['driver']) return false;
        }
        if (isset($definition['when']['event'])) {
            if ($hookEvent->getEvent() !== $definition['when']['event']) return false;
        }
        if (isset($definition['when']['target'])) {
            if ($hookEvent->getTarget() !== $definition['when']['target']) return false;
        }

        return true;
    }
}