<?php

namespace Monyxie\Webhooked\Logger;

/**
 * Class LoggerFactory
 *
 * @package Monyxie\Webhooked\Logger
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