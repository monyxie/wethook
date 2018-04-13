<?php

namespace Monyxie\Webhooked\Server;


/**
 * Class Task
 * @package Monyxie\Webhooked\Server
 */
class Task {
    /**
     * @var string Working directory
     */
    private $workingDirectory;
    /**
     * @var string Command to execute
     */
    private $command;

    public function __construct($command, $workingDirectory) {
        $this->command = $command;
        $this->workingDirectory = $workingDirectory;
    }

    /**
     * @return string
     */
    public function getWorkingDirectory(): string {
        return $this->workingDirectory;
    }

    /**
     * @return string
     */
    public function getCommand(): string {
        return $this->command;
    }
}