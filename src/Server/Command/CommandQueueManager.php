<?php

namespace Monyxie\Webhooked\Server\Command;


use Evenement\EventEmitter;
use Monyxie\Webhooked\Server\Command;
use React\EventLoop\LoopInterface;

/**
 * Class CommandQueue
 * @package Monyxie\Webhooked\Server\Command
 */
class CommandQueueManager extends EventEmitter {
    /**
     * @var \SplQueue[]
     */
    private $queues = [];
    /**
     * @var CommandExecutor[]
     */
    private $executors = [];
    /**
     * @var LoopInterface
     */
    private $loop;

    /**
     * CommandQueueManager constructor.
     * @param LoopInterface $loop
     */
    public function __construct(LoopInterface $loop) {
        $this->loop = $loop;
    }

    /**
     * @param Command $command
     */
    public function enqueue(Command $command) {
        $dir = $command->getWorkingDirectory();
        if (! isset($this->queues[$dir])) {
            $queue = new \SplQueue();
            $executor = new CommandExecutor($this->loop, $queue);

            $executor->on(CommandExecutor::EVENT_BEFORE_COMMAND, function (...$arguments) {
                $this->emit(CommandExecutor::EVENT_BEFORE_COMMAND, $arguments);
            });
            $executor->on(CommandExecutor::EVENT_AFTER_COMMAND, function (...$arguments) {
                $this->emit(CommandExecutor::EVENT_AFTER_COMMAND, $arguments);
            });

            $this->queues[$dir] = $queue;
            $this->executors[$dir] = $executor;
        }

        $this->queues[$dir]->enqueue($command);
        $this->executors[$dir]->notify();
    }
}