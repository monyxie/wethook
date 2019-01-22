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

        $this->logger->log(LogLevel::INFO, 'Task queued.', ['command' => $task->getCommand(), 'workingDirectory' => $task->getWorkingDirectory()]);

        if (!$this->isRunning) {
            $this->isRunning = true;

            $this->loop->addTimer(0, function() {
                $this->run();
            });
        }
    }

    /**
     * Execute commands through the queue sequentially
     */
    private function run()
    {
        $resume = null;
        $generatorMaker = function () use (&$resume) {
            while (!$this->queue->isEmpty()) {
                /* @var Task */
                $task = $this->queue->dequeue();
                $this->runCommand($task->getCommand(), $task->getWorkingDirectory(), $task->getEnvironment(), $resume);
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
     * @param string $cmd
     * @param string $cwd
     * @param array $env
     * @param callable $onExit
     */
    private function runCommand(string $cmd, string $cwd, array $env, callable $onExit)
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

        // TODO pass event data as environment variables
        $process = new Process($cmd, $cwd, null);
        $process->start($this->loop);
        $process->stdout->on('data', $appendOutput);
        $process->stderr->on('data', $appendOutput);
        $process->on('exit', $handleProcessExit);
    }
}