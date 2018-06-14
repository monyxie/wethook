<?php

namespace Monyxie\Webhooked\Server\Task;


use Evenement\EventEmitter;
use Monyxie\Webhooked\Server\Task;
use React\ChildProcess\Process;
use React\EventLoop\LoopInterface;

/**
 * Class TaskRunner
 * @package Monyxie\Webhooked\Server\Task
 */
class TaskRunner extends EventEmitter {
    const EVENT_BEFORE_RUN = 'taskRunner.beforeCommand';
    const EVENT_AFTER_RUN = 'taskRunner.afterCommand';

    private $isRunning = false;
    /**
     * @var \SplQueue
     */
    private $queue;
    /**
     * @var LoopInterface
     */
    private $loop;

    public function __construct(LoopInterface $loop, \SplQueue $queue) {
        $this->loop = $loop;
        $this->queue = $queue;
    }

    public function notify() {
        if (! $this->isRunning) {
            $this->run();
        }
    }

    /**
     * Execute commands through the queue sequentially
     * @param $commands
     * @param $cwd
     */
    private function run() {
        if ($this->isRunning) {
            return;
        }
        $this->isRunning = true;

        $resume = null;
        $generatorMaker = function() use (&$resume) {
            while (! $this->queue->isEmpty()) {
                /* @var Task */
                $command = $this->queue->dequeue();
                yield $this->runCommand($command->getCommand(), $command->getWorkingDirectory(), $resume);
            }
            $this->isRunning = false;
        };

        $generator = $generatorMaker();
        $resume = function() use ($generator) {
            $generator->next();
        };

        $generator->current();
    }

    /**
     * Run one command
     *
     * @param $command
     * @param $cwd
     * @param $onExit
     * @throws \LogicException
     * @throws \RuntimeException
     */
    private function runCommand($command, $cwd, $onExit) {
        $this->emit(static::EVENT_BEFORE_RUN, [
            $command,
            $cwd,
        ]);

        $output = '';
        $appendOutput = function($chunk) use (&$output) {
            $output .= $chunk;
        };
        $handleProcessExit = function() use ($cwd, $command, $onExit, &$output) {
            $this->emit(static::EVENT_AFTER_RUN, [
                $command,
                $cwd,
                $output,
            ]);
            return call_user_func($onExit);
        };

        $process = new Process($command, $cwd);
        $process->start($this->loop);
        $process->stdout->on('data', $appendOutput);
        $process->stderr->on('data', $appendOutput);
        $process->on('exit', $handleProcessExit);
    }
}