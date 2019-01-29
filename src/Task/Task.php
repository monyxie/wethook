<?php

namespace Monyxie\Wethook\Task;

/**
 * Class Task
 * @package Monyxie\Wethook\Http
 */
class Task
{
    /**
     * @var string Working directory
     */
    private $workingDirectory;

    /**
     * @var string Command to execute
     */
    private $command;

    /**
     * @var array Environment variables
     */
    private $environment;

    public function __construct(string $command, string $workingDirectory, array $environment)
    {
        $this->command = $command;
        $this->workingDirectory = $workingDirectory;
        $this->environment = $environment;
    }

    /**
     * @return string
     */
    public function getWorkingDirectory(): string
    {
        return $this->workingDirectory;
    }

    /**
     * @return string
     */
    public function getCommand(): string
    {
        return $this->command;
    }

    /**
     * @return array
     */
    public function getEnvironment()
    {
        return $this->environment;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'command' => $this->getCommand(),
            'workingDirectory' => $this->getWorkingDirectory(),
            'environment' => $this->getEnvironment()
        ];
    }
}