<?php

namespace Monyxie\Webhooked\Server;

use Evenement\EventEmitter;
use Monyxie\Webhooked\Config\Config;
use Monyxie\Webhooked\Request\BasicRequestInterface;
use React\ChildProcess\Process;
use React\EventLoop\LoopInterface;
use React\Http\Response;

/**
 * Class Runner
 * @package Monyxie\Webhooked\Server
 */
class Runner extends EventEmitter
{
    const EVENT_BEFORE_COMMAND = 'beforeCommand';
    const EVENT_AFTER_COMMAND = 'afterCommand';

    /**
     * @var Config
     */
    private $config;
    /**
     * @var LoopInterface
     */
    private $loop;

    public function __construct(LoopInterface $loop, Config $config) {
        $this->loop = $loop;
        $this->config = $config;
    }

    /**
     * @param BasicRequestInterface $request
     * @return string
     * @throws \Monyxie\Webhooked\Config\ConfigKeyNotFoundException
     */
    public function run(BasicRequestInterface $request) {
        return $this->handleRequest($request);
    }

    /**
     * @param BasicRequestInterface $request
     *
     * @return string
     * @throws \Monyxie\Webhooked\Config\ConfigKeyNotFoundException
     */
    public function handleRequest(BasicRequestInterface $request) {
        if (! $request->validateSecret($this->config->get('password'))) {
            return 'Invalid secret';
        }
        if ($request->getEventName() !== 'push') {
            return 'Unsupported event';
        }

        try {
            return $this->handleRepoName($request->getRepositoryFullName());
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * 处理仓库名
     *
     * @param $repoName
     *
     * @return null|string 错误信息
     * @throws \Monyxie\Webhooked\Config\ConfigKeyNotFoundException
     */
    private function handleRepoName($repoName) {
        $repos = $this->config->get('repos');

        $matched = false;
        foreach ($repos as $repo) {
            if ($repo['name'] === $repoName) {
                $this->runCommands($repo['commands'], $repo['path']);
                $matched = true;
            }
        }

        return $matched ? 'OK' : 'No matching repository found : ' . $repoName;
    }

    /**
     * @param $commands
     * @param $cwd
     */
    private function runCommands($commands, $cwd) {

        $resume = null;
        $generatorMaker = function() use ($commands, $cwd, &$resume) {
            foreach ($commands as $cmd) {
                yield $this->runCommand($cmd, $cwd, $resume);
            }
        };

        $generator = $generatorMaker();
        $resume = function() use ($generator) {
            $generator->next();
        };

        $generator->current();
    }

    /**
     * 执行命令
     *
     * @param $command
     * @param $cwd
     * @param $onExit
     * @throws \LogicException
     * @throws \RuntimeException
     */
    private function runCommand($command, $cwd, $onExit) {
        $that = $this;

        $this->emit(static::EVENT_BEFORE_COMMAND, [
            'command' => $command,
            'cwd' => $cwd,
        ]);

        $output = '';
        $appendOutput = function($chunk) use (&$output) {
            $output .= $chunk;
        };
        $handleProcessExit = function() use ($that, $cwd, $command, $onExit, &$output) {
            $that->emit(static::EVENT_AFTER_COMMAND, [
                $command,
                $cwd,
                $output,
            ]);
            return call_user_func($onExit);
        };

        $process = new Process($command, $cwd);
        $process->start($this->loop);
        $process->stdout->on('data', $appendOutput);
        $process->stderr->on('data', $appendOutput);
        $process->on('exit', $handleProcessExit);
    }
}