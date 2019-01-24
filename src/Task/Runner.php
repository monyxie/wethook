<?php

namespace Monyxie\Wethook\Task;

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
     * @var int The latest time a task was enqueued.
     */
    private $latestEnqueuedAt = 0;
    /**
     * @var int The latest time a task finished running.
     */
    private $latestFinishedAt = 0;

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
     * Recent results.
     * @var \SplQueue
     */
    private $results;

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
        $this->results = new \SplQueue();
    }

    /**
     * @param Task $task
     */
    public function enqueue(Task $task)
    {
        $this->queue->enqueue($task);
        $this->numEnqueued++;
        $this->latestEnqueuedAt = time();

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
                $this->runTask($task, $resume);
                yield;
            }
            $this->isRunning = false;
        };

        $generator = $generatorMaker();
        $resume = function (Result $result) use ($generator) {
            $this->numFinished++;
            $this->latestFinishedAt = time();

            $this->results->enqueue($result);
            if ($this->results->count() > 10) {
                $this->results->dequeue();
            }

            $generator->next();
        };

        $generator->current();
    }

    /**
     * Run one command.
     *
     * @param Task $task
     * @param callable $onExit
     */
    private function runTask(Task $task, callable $onExit)
    {
        $startTime = time();
        $output = '';
        $appendOutput = function ($chunk) use (&$output) {
            $output .= $chunk;
        };
        $handleProcessExit = function ($exitCode, $termSignal) use ($startTime, &$output, $task, $onExit) {
            $finishTime = time();

            $logLevel = $exitCode === 0 ? LogLevel::INFO : LogLevel::WARNING;
            $this->logger->log($logLevel, 'Command finished running.', [
                'command' => $task->getCommand(),
                'workingDirectory' => $task->getWorkingDirectory(),
                'exitCode' => $exitCode
            ]);

            $result = new Result($task, $startTime, $finishTime, $exitCode, $output);
            return call_user_func($onExit, $result);
        };

        // TODO pass event data as environment variables
        $process = new Process($task->getCommand(), $task->getWorkingDirectory(), null);
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
    public function getLatestEnqueuedAt(): int
    {
        return $this->latestEnqueuedAt;
    }

    /**
     * @return int
     */
    public function getLatestFinishedAt(): int
    {
        return $this->latestFinishedAt;
    }

    /**
     * @return array
     */
    public function getResults(): array
    {
        $results = [];
        foreach ($this->results as $result) {
            $results []= $result;
        }

        return $results;
    }
}