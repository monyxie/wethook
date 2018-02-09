<?php

namespace Monyxie\Webhooked\Logger;

class Logger {
    public function __construct($pathToLogFile) {
        $this->logFile = $pathToLogFile;
    }

    public function write($content) {
        file_put_contents($this->logFile, date('Y-m-d H:i:s') . ' ' . trim($content, "\n") . "\n", FILE_APPEND);
    }
}