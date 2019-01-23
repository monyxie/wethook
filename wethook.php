<?php

require(__DIR__ . '/vendor/autoload.php');

define('STARTUP_TIME', time());
define('VERSION', '0.1');
define('PATH_ROOT', __DIR__);
define('PATH_SRC', PATH_ROOT . '/src');
define('PATH_CONFIG', PATH_ROOT . '/config');
define('PATH_RUNTIME', PATH_ROOT . '/runtime');

$doc = <<<DOC
Usage:
  wethook.php [-l addr:port] [--debug]
  wethook.php (-h | --help)
  wethook.php --version

Options:
  -l --listen addr:port  Set the address and port to listen.
  --debug                Run in debug mode.
  -h --help              Show this screen.
  --version              Show version.

DOC;

$args = Docopt::handle($doc, array('version' => VERSION));
$defs = [];

if ($args['--listen']) {
    $defs['listen'] = $args['--listen'];
}

$builder = new \DI\ContainerBuilder();
if ($args['--debug']) {
    $builder->enableCompilation(PATH_RUNTIME . '/tmp');
    $builder->writeProxiesToFile(true, PATH_RUNTIME . '/tmp/proxies');
}
$builder->addDefinitions(PATH_CONFIG . '/definitions.php');
if (file_exists(PATH_CONFIG . '/config.php')) {
    $builder->addDefinitions(PATH_CONFIG . '/config.php');
}
$builder->addDefinitions($defs);

$container = $builder->build();
$container->get(\Monyxie\Wethook\Http\Server::class)->run();
$container->get(\React\EventLoop\LoopInterface::class)->run();
