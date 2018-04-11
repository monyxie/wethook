<?php

use Monyxie\Webhooked\Logger\CompositeLogger;
use Monyxie\Webhooked\Logger\LoggerInterface;
use PHPUnit\Framework\TestCase;

class CompositeLoggerTest extends TestCase {

    public function testWrite() {
        $logContent = 'log content';

        $loggers = [];
        for ($i = 0; $i < 3; $i++) {
            $logger = $this->getMockForAbstractClass(LoggerInterface::class);
            $logger->expects($this->once())
                ->method('write')
                ->with($logContent);
            $loggers []= $logger;
        }

        $compositeLogger = new CompositeLogger($loggers);
        $compositeLogger->write($logContent);
    }
}
