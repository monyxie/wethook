<?php

namespace Monyxie\Wethook\Task;

/**
 * Class Result
 * @package Monyxie\Wethook\Http
 */
class Result
{
    /**
     * @var Task
     */
    private $task;
    /**
     * @var int
     */
    private $exitCode;
    /**
     * @var string
     */
    private $output;
    /**
     * @var int
     */
    private $startTime;
    /**
     * @var int
     */
    private $finishTime;

    /**
     * Task constructor.
     * @param Task $task
     * @param int $startTime
     * @param int $finishTime
     * @param int $exitCode
     * @param string $output
     */
    public function __construct(Task $task, int $startTime, int $finishTime, int $exitCode, string $output)
    {
        $this->task = $task;
        $this->startTime = $startTime;
        $this->finishTime = $finishTime;
        $this->exitCode = $exitCode;
        $this->output = $output;
    }

    /**
     * @return Task
     */
    public function getTask(): Task
    {
        return $this->task;
    }

    /**
     * @return int
     */
    public function getExitCode(): int
    {
        return $this->exitCode;
    }

    /**
     * @return string
     */
    public function getOutput(): string
    {
        return $this->output;
    }

    /**
     * @return int
     */
    public function getStartTime(): int
    {
        return $this->startTime;
    }

    /**
     * @return int
     */
    public function getFinishTime(): int
    {
        return $this->finishTime;
    }
}