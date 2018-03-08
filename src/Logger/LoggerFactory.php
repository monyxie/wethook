<?php

namespace Monyxie\Webhooked\Logger;

/**
 * Class LoggerFactory
 *
 * @package Monyxie\Webhooked\Logger
 */
abstract class LoggerFactory {

    /**
     * @return LoggerInterface
     */
    public static function get() {
        static $instance;
        if (! $instance) {
            $instance = new CompositeLogger([
                new FileLogger(PATH_RUNTIME . '/webhooked.log'),
                new ConsoleLogger(),
            ]);
        }

        return $instance;
    }
}