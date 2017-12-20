<?php

namespace Puller\Config;

/**
 * Class ConfigFactory
 *
 * @package Puller\Config
 */
abstract class ConfigFactory {

    /**
     * @return Config
     */
    public static function get() {
        static $instance;
        if (! $instance) {
            $instance = new Config(PATH_CONFIG . '/main.php');
        }

        return $instance;
    }
}