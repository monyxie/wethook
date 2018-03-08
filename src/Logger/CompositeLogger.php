<?php

namespace Monyxie\Webhooked\Logger;

/**
 * Class CompositeLogger
 * @package Monyxie\Webhooked\Logger
 */
class CompositeLogger implements LoggerInterface {
    /**
     * @var array
     */
    private $loggers;

    public function __construct(array $loggers) {
        $this->loggers = $loggers;
    }

    public function write(string $content) {
        foreach ($this->loggers as $logger) {
            call_user_func([$logger, 'write'], $content);
        }
    }
}