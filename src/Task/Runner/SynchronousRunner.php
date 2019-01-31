<?php

namespace Monyxie\Wethook\Task\Runner;

use Evenement\EventEmitterTrait;
use Monyxie\Wethook\Task\Result;
use Monyxie\Wethook\Task\Task;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use React\EventLoop\LoopInterface;
use Symfony\Component\Process\Process;

/**
 * Class SynchronousRunner
 */
class SynchronousRunner implements RunnerInterface
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
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var LoopInterface
     */
    private $loop;

    /**
     * Runner constructor.
     * @param LoopInterface $loop
     * @param LoggerInterface $logger
     */
    public function __construct(LoopInterface $loop, LoggerInterface $logger)
    {
        $this->queue = new \SplQueue();
        $this->logger = $logger;
        $this->loop = $loop;
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

            // This is a hack to make the task run after the http response is sent.
            $this->loop->addTimer(0, function () {
                $this->loop->addTimer(0, function () {
                    $this->run();
                });
            });
        }
    }

    /**
     * Execute commands through the queue sequentially
     */
    private function run()
    {
        while (!$this->queue->isEmpty()) {
            /* @var Task */
            $task = $this->queue->dequeue();
            $this->runTask($task, function (Task $task, Result $result) {
                $this->emit('finish', [
                    $task->toArray(),
                    $result->toArray(),
                ]);
            });
        }

        $this->isRunning = false;
        $this->emit('idle');
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

        // TODO pass event data as environment variables
        $process = Process::fromShellCommandline($task->getCommand(), $task->getWorkingDirectory(), null);
        $exitCode = $process->run(function ($type, $chunk) use (&$output) {
            $output .= $chunk;
        });

        $finishTime = time();

        $logLevel = $exitCode === 0 ? LogLevel::INFO : LogLevel::WARNING;
        $this->logger->log($logLevel, 'Command finished running.', [
            'command' => $task->getCommand(),
            'workingDirectory' => $task->getWorkingDirectory(),
            'exitCode' => $exitCode
        ]);

        $result = new Result($startTime, $finishTime, $exitCode, $output);
        call_user_func($onExit, $task, $result);
    }
}