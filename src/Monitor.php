<?php


namespace Monyxie\Wethook;


use Monyxie\Wethook\Driver\Registry;
use Monyxie\Wethook\Task\Runner\RunnerInterface;
use React\EventLoop\LoopInterface;
use React\EventLoop\Timer\TimerInterface;

class Monitor
{
    private $runningSince = 0;

    private $loopClass = '';
    private $registeredDrivers = [];
    private $runnerStatus = 'idle';
    private $enqueuedTasks = 0;
    private $finishedTasks = 0;
    private $lastEnqueuedAt = 0;
    private $lastFinishedAt = 0;

    private $recentTasks = [];

    private $memAllocated = 0;
    private $memUsage = 0;
    private $memPeakUsage = 0;
    private $memPeakAllocated = 0;


    /**
     * @var TimerInterface[]
     */
    private $timers = [];

    /**
     * @var LoopInterface
     */
    private $loop;

    public function __construct(LoopInterface $loop)
    {
        $this->loop = $loop;
        $this->runningSince = time();

        $loopClassSegments = explode('\\', get_class($loop));
        $loopClass = end($loopClassSegments);
        $this->loopClass = $loopClass;


        $this->memAllocated = memory_get_usage(true);
        $this->memUsage = memory_get_usage(false);
        $this->memPeakAllocated = memory_get_peak_usage(true);
        $this->memPeakUsage = memory_get_peak_usage(false);

        $this->timers['memory'] = $this->loop->addPeriodicTimer(5, function() {
            $this->memAllocated = memory_get_usage(true);
            $this->memUsage = memory_get_usage(false);
            $this->memPeakAllocated = memory_get_peak_usage(true);
            $this->memPeakUsage = memory_get_peak_usage(false);
        });
    }

    public function __destruct()
    {
        foreach ($this->timers as $timer) {
            $timer->cancel();
        }
    }

    public function monitorRegistry(Registry $registry)
    {
        $drivers = [];
        foreach ($registry as $driver) {
            $drivers [] = $driver->getIdentifier();
        }

        $this->registeredDrivers = $drivers;
    }

    public function monitorRunner(RunnerInterface $runner)
    {
        $runner->on('enqueue', function ($task) {
            $this->lastEnqueuedAt = time();
            $this->enqueuedTasks++;
        });

        $runner->on('finish', function ($task, $result) {
            $this->lastFinishedAt = time();
            $this->finishedTasks++;

            $result['task'] = $task;
            $this->recentTasks []= $result;
            if (count($this->recentTasks) > 20) {
                array_shift($this->recentTasks);
            }
        });

        $runner->on('busy', function () {
            $this->runnerStatus = 'busy';
        });

        $runner->on('idle', function () {
            $this->runnerStatus = 'idle';
        });
    }

    /**
     * @return string
     */
    public function getLoopClass(): string
    {
        return $this->loopClass;
    }

    /**
     * @return array
     */
    public function getRegisteredDrivers(): array
    {
        return $this->registeredDrivers;
    }

    /**
     * @return string
     */
    public function getRunnerStatus(): string
    {
        return $this->runnerStatus;
    }

    /**
     * @return int
     */
    public function getEnqueuedTasks(): int
    {
        return $this->enqueuedTasks;
    }

    /**
     * @return int
     */
    public function getFinishedTasks(): int
    {
        return $this->finishedTasks;
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

    /**
     * @return array
     */
    public function getRecentTasks(): array
    {
        return $this->recentTasks;
    }

    /**
     * @return int
     */
    public function getMemAllocated(): int
    {
        return $this->memAllocated;
    }

    /**
     * @return int
     */
    public function getMemUsage(): int
    {
        return $this->memUsage;
    }

    /**
     * @return int
     */
    public function getMemPeakUsage(): int
    {
        return $this->memPeakUsage;
    }

    /**
     * @return int
     */
    public function getMemPeakAllocated(): int
    {
        return $this->memPeakAllocated;
    }

    /**
     * @return int
     */
    public function getRunningSince(): int
    {
        return $this->runningSince;
    }



}