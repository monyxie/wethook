<?php

namespace Monyxie\Webhooked\Logger;

/**
 * Combine multiple loggers.
 * @package Monyxie\Webhooked\Logger
 */
class CompositeLogger implements LoggerInterface {
    /**
     * @var LoggerInterface[]
     */
    private $loggers;

    /**
     * CompositeLogger constructor.
     * @param LoggerInterface[] $loggers
     */
    public function __construct(array $loggers) {
        $this->loggers = $loggers;
    }

    /**
     * @inheritdoc
     */
    public function write(string $content) {
        foreach ($this->loggers as $logger) {
            $logger->write($content);
        }
    }
}