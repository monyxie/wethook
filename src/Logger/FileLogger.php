<?php

namespace Monyxie\Webhooked\Logger;

/**
 * Class FileLogger
 * @package Monyxie\Webhooked\Logger
 */
class FileLogger implements LoggerInterface {
    /**
     * FileLogger constructor.
     * @param $pathToLogFile
     */
    public function __construct($pathToLogFile) {
        $this->logFile = $pathToLogFile;
    }

    /**
     * @param string $content
     */
    public function write(string $content) {
        file_put_contents($this->logFile, date('[Y-m-d H:i:s]') . ' ' . trim($content, "\n") . "\n", FILE_APPEND);
    }
}