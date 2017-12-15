<?php

namespace Puller\Logger;

class Logger {
    public function __construct($pathToLogFile) {
        $this->logFile = $pathToLogFile;
    }

    public function write($content) {
        file_put_contents($this->logFile, date('Y-m-d H:i:s') . ' ' . $content . "\n", FILE_APPEND);
    }
}