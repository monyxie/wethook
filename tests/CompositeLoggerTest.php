<?php


use Monyxie\Webhooked\Logger\CompositeLogger;
use PHPUnit\Framework\TestCase;

class CompositeLoggerTest extends TestCase {

    public function testWrite() {
        $logContent = 'log content';

        $logger1 = $this->getMockForAbstractClass(\Monyxie\Webhooked\Logger\LoggerInterface::class);
        $logger1->expects($this->once())
            ->method('write')
            ->with($logContent);
        $logger2 = $this->getMockForAbstractClass(\Monyxie\Webhooked\Logger\LoggerInterface::class);
        $logger2->expects($this->once())
            ->method('write')
            ->with($logContent);

        $compositeLogger = new CompositeLogger([$logger1, $logger2]);

        $compositeLogger->write($logContent);
    }
}
