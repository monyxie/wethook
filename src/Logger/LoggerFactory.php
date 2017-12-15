<?php

namespace Puller\Logger;

use Puller\Config\ConfigFactory;

/**
 * Class LoggerFactory
 *
 * @package Puller\Logger
 */
abstract class LoggerFactory {

    /**
     * @return Logger
     */
    public static function create() {
        static $instance;
        if (! $instance) {
            $instance = new Logger(ConfigFactory::create()->get('logfile'));
        }

        return $instance;
    }
}