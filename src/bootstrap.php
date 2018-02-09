<?php

require(__DIR__ . '/../vendor/autoload.php');

define('PATH_ROOT', dirname(__DIR__));
define('PATH_SRC', PATH_ROOT . '/src');
define('PATH_CONFIG', PATH_ROOT . '/config');
define('PATH_RUNTIME', PATH_ROOT . '/runtime');

$server = new \Monyxie\Webhooked\Server\Server();
$server->run();