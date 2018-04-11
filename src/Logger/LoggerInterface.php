<?php
/**
 * Created by PhpStorm.
 * User: monyxie
 * Date: 18-4-1
 * Time: 下午11:04
 */

namespace Monyxie\Webhooked\Logger;

/**
 * Interface LoggerInterface
 * @package Monyxie\Webhooked\Logger
 */
interface LoggerInterface {
    /**
     * Write log.
     * @param string $content
     * @return void
     */
    public function write(string $content);
}