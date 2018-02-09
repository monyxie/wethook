<?php

namespace Monyxie\Webhooked\Config;

/**
 * Class Config
 *
 * @package Monyxie\Webhooked\Config
 */
class Config {

    /**
     * Config constructor.
     *
     * @param $path
     */
    public function __construct($path) {
        $this->data = json_decode(file_get_contents($path), true);
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