<?php

namespace Monyxie\Wethook\Task;


use Evenement\EventEmitterInterface;

/**
 * Class RunnerInterface
 */
interface RunnerInterface extends EventEmitterInterface
{
    /**
     * @param Task $task
     */
    public function enqueue(Task $task): void;
}