<?php

namespace Monyxie\Webhooked\Server\Task;


use Evenement\EventEmitter;
use Monyxie\Webhooked\Server\Task;
use React\EventLoop\LoopInterface;

/**
 * Class TaskQueue
 * @package Monyxie\Webhooked\Server\Task
 */
class TaskQueueManager extends EventEmitter {
    const EVENT_AFTER_ENQUEUE = 'taskQueueManager.afterEnqueue';
    /**
     * @var \SplQueue[]
     */
    private $queues = [];
    /**
     * @var TaskRunner[]
     */
    private $executors = [];
    /**
     * @var LoopInterface
     */
    private $loop;

    /**
     * @var int
     */
    private $numQueueing = 0;
    /**
     * @var int
     */
    private $numRunning = 0;

    /**
     * @param LoopInterface $loop
     */
    public function __construct(LoopInterface $loop) {
        $this->loop = $loop;
    }

    /**
     * @param Task $task
     */
    public function enqueue(Task $task) {
        $dir = $task->getWorkingDirectory();
        if (! isset($this->queues[$dir])) {
            $queue = new \SplQueue();
            $executor = new TaskRunner($this->loop, $queue);

            $executor->on(TaskRunner::EVENT_BEFORE_RUN, function (...$arguments) {
                --$this->numQueueing;
                ++$this->numRunning;
                $this->emit(TaskRunner::EVENT_BEFORE_RUN, $arguments);
            });
            $executor->on(TaskRunner::EVENT_AFTER_RUN, function (...$arguments) {
                --$this->numRunning;
                $this->emit(TaskRunner::EVENT_AFTER_RUN, $arguments);
            });

            $this->queues[$dir] = $queue;
            $this->executors[$dir] = $executor;
        }

        $this->queues[$dir]->enqueue($task);
        ++$this->numQueueing;
        $this->emit(static::EVENT_AFTER_ENQUEUE, [$task->getCommand(), $task->getWorkingDirectory()]);

        $this->loop->addTimer(0, function() use ($dir) {
            $this->executors[$dir]->notify();
        });
    }

    /**
     * @return int
     */
    public function getNumQueueing(): int {
        return $this->numQueueing;
    }

    /**
     * @return int
     */
    public function getNumRunning(): int {
        return $this->numRunning;
    }
}