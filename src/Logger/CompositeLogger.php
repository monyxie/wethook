<?php

namespace Monyxie\Webhooked\Logger;

/**
 * Class CompositeLogger
 * @package Monyxie\Webhooked\Logger
 */
class CompositeLogger implements LoggerInterface {
    /**
     * @var LoggerInterface[]
     */
    private $loggers;

    public function __construct(array $loggers) {
        $this->loggers = $loggers;
    }

    public function write(string $content) {
        foreach ($this->loggers as $logger) {
            $logger->write($content);
        }
    }
}