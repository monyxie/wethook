<?php

namespace Puller\Logger;

/**
 * Class LoggerFactory
 *
 * @package Puller\Logger
 */
abstract class LoggerFactory {

    /**
     * @return Logger
     */
    public static function get() {
        static $instance;
        if (! $instance) {
            $instance = new Logger(PATH_RUNTIME . '/webhooked.log');
        }

        return $instance;
    }
}