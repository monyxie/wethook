<?php

namespace Monyxie\Webhooked\Task;

use Evenement\EventEmitterInterface;
use Evenement\EventEmitterTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use React\ChildProcess\Process;
use React\EventLoop\LoopInterface;

/**
 * Class Runner
 */
class Runner implements EventEmitterInterface
{
    use EventEmitterTrait;

    private $isRunning = false;
    /**
     * @var \SplQueue
     */
    private $queue;
    /**
     * @var LoopInterface
     */
    private $loop;
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Runner constructor.
     * @param LoopInterface $loop
     * @param LoggerInterface $logger
     */
    public function __construct(LoopInterface $loop, LoggerInterface $logger)
    {
        $this->loop = $loop;
        $this->queue = new \SplQueue();
        $this->logger = $logger;
    }

    public function enqueue(Task $task)
    {
        $this->queue->enqueue($task);

        if (!$this->isRunning) {
            $this->run();
        }
    }

    /**
     * Execute commands through the queue sequentially
     */
    private function run()
    {
        if ($this->isRunning) {
            return;
        }
        $this->isRunning = true;

        $resume = null;
        $generatorMaker = function () use (&$resume) {
            while (!$this->queue->isEmpty()) {
                /* @var Task */
                $task = $this->queue->dequeue();
                $this->runCommand($task->getCommand(), $task->getWorkingDirectory(), $resume);
                yield;
            }
            $this->isRunning = false;
        };

        $generator = $generatorMaker();
        $resume = function () use ($generator) {
            $generator->next();
        };

        $generator->current();
    }

    /**
     * Run one command.
     *
     * @param $cmd
     * @param $cwd
     * @param $onExit
     */
    private function runCommand($cmd, $cwd, $onExit)
    {
        $output = '';
        $appendOutput = function ($chunk) use (&$output) {
            $output .= $chunk;
        };
        $handleProcessExit = function ($exitCode, $termSignal) use ($cwd, $cmd, $onExit) {
            $logLevel = $exitCode === 0 ? LogLevel::INFO : LogLevel::WARNING;
            $this->logger->log($logLevel, 'Command finished running.', ['command' => $cmd, 'workingDirectory' => $cwd, 'exitCode' => $exitCode]);
            return call_user_func($onExit);
        };

        $process = new Process($cmd, $cwd);
        $process->start($this->loop);
        $process->stdout->on('data', $appendOutput);
        $process->stderr->on('data', $appendOutput);
        $process->on('exit', $handleProcessExit);
    }
}