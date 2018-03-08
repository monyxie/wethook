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
     * @param string $content
     * @return mixed
     */
    public function write(string $content);
}