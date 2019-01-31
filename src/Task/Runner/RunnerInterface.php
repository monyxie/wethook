<?php

namespace Monyxie\Wethook\Task\Runner;


use Evenement\EventEmitterInterface;
use Monyxie\Wethook\Task\Task;

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