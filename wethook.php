<?php

require(__DIR__ . '/vendor/autoload.php');

define('IS_WINDOWS', substr(PHP_OS, 0, 3) === 'WIN');
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

if (IS_WINDOWS) {
    $builder->addDefinitions([
        Monyxie\Wethook\Task\Runner\RunnerInterface::class =>
            \DI\autowire(Monyxie\Wethook\Task\Runner\SynchronousRunner::class),
    ]);
}

$container = $builder->build();

if (IS_WINDOWS) {
    $container->get(\Psr\Log\LoggerInterface::class)
        ->warning('Running on Windows has severe performance issues and is for experiments only.');
}
$container->get(\Monyxie\Wethook\Server::class)->run();
$container->get(\React\EventLoop\LoopInterface::class)->run();
