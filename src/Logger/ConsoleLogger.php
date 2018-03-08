<?php

namespace Monyxie\Webhooked\Logger;

/**
 * Class ConsoleLogger
 * @package Monyxie\Webhooked\Logger
 */
class ConsoleLogger implements LoggerInterface {
    /**
     * @param string $content
     * @return mixed|void
     */
    public function write(string $content) {
        echo date('[Y-m-d H:i:s]') . ' ' . trim($content, "\n") . "\n";
    }
}