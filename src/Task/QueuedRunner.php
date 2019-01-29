<?php

namespace Monyxie\Wethook\Task;

use Evenement\EventEmitterTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use React\ChildProcess\Process;
use React\EventLoop\LoopInterface;

/**
 * Class QueuedRunner
 */
class QueuedRunner implements RunnerInterface
{
    use EventEmitterTrait;

    /**
     * @var bool Whether the task runner is running.
     */
    private $isRunning = false;

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
     * @inheritdoc
     */
    public function enqueue(Task $task): void
    {
        $this->queue->enqueue($task);
        $this->emit('enqueue', [(array)$task]);

        $this->logger->log(LogLevel::INFO, 'Task queued.', ['command' => $task->getCommand(), 'workingDirectory' => $task->getWorkingDirectory()]);

        if (!$this->isRunning) {
            $this->isRunning = true;
            $this->emit('busy');

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
            $this->emit('idle');
        };

        $generator = $generatorMaker();
        $resume = function (Task $task, Result $result) use ($generator) {
            $this->emit('finish', [
                $task->toArray(),
                $result->toArray(),
            ]);

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
        $this->logger->log(LogLevel::INFO, 'Task started.', ['command' => $task->getCommand(), 'workingDirectory' => $task->getWorkingDirectory()]);
        $this->emit('start', [(array)$task]);

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

            $result = new Result($startTime, $finishTime, $exitCode, $output);
            return call_user_func($onExit, $task, $result);
        };

        // TODO pass event data as environment variables
        $process = new Process($task->getCommand(), $task->getWorkingDirectory(), null);
        $process->start($this->loop);
        $process->stdout->on('data', $appendOutput);
        $process->stderr->on('data', $appendOutput);
        $process->on('exit', $handleProcessExit);
    }
}