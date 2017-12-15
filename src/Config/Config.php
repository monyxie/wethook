<?php

namespace Puller\Config;

/**
 * Class Config
 *
 * @package Puller\Config
 */
class Config {

    /**
     * Config constructor.
     *
     * @param $path
     */
    public function __construct($path) {
        $this->data = require($path);
    }

    /**
     * @param $key
     *
     * @return mixed
     */
    public function get($key) {
        $keys = explode('.', $key);

        $value = $this->data;
        foreach ($keys as $item) {
            if (! isset($value[$item])) {
                throw new \Exception('找不到配置项: ' . $key);
            }
            $value = $value[$item];
        }

        return $value;
    }
}