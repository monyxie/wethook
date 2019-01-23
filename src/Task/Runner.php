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

    /**
     * @var bool Whether the task runner is running.
     */
    private $isRunning = false;
    /**
     * @var int Total number of tasks that have been enqueued.
     */
    private $numEnqueued = 0;
    /**
     * @var int Total number of tasks that have finished running.
     */
    private $numFinished = 0;
    /**
     * @var int The last time a task was enqueued.
     */
    private $lastEnqueuedAt = 0;
    /**
     * @var int The last time a task finished running.
     */
    private $lastFinishedAt = 0;

    /**
     * @var \SplQueue The queue.
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

    /**
     * @param Task $task
     */
    public function enqueue(Task $task)
    {
        $this->queue->enqueue($task);
        $this->numEnqueued++;
        $this->lastEnqueuedAt = time();

        $this->logger->log(LogLevel::INFO, 'Task queued.', ['command' => $task->getCommand(), 'workingDirectory' => $task->getWorkingDirectory()]);

        if (!$this->isRunning) {
            $this->isRunning = true;

            $this->loop->addTimer(0, function () {
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
            $this->numFinished++;
            $this->lastFinishedAt = time();
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

    /**
     * @return bool
     */
    public function isRunning(): bool
    {
        return $this->isRunning;
    }

    /**
     * @return int
     */
    public function getNumEnqueued(): int
    {
        return $this->numEnqueued;
    }

    /**
     * @return int
     */
    public function getNumFinished(): int
    {
        return $this->numFinished;
    }

    /**
     * @return int
     */
    public function getLastEnqueuedAt(): int
    {
        return $this->lastEnqueuedAt;
    }

    /**
     * @return int
     */
    public function getLastFinishedAt(): int
    {
        return $this->lastFinishedAt;
    }
}