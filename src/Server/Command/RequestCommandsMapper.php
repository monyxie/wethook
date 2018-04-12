<?php

namespace Monyxie\Webhooked\Server\Command;

use Monyxie\Webhooked\Config\Config;
use Monyxie\Webhooked\Request\BasicRequestInterface;
use Monyxie\Webhooked\Server\Command;

/**
 * Map request to commands according to configuration.
 * @package Monyxie\Webhooked\Server
 */
class RequestCommandsMapper
{

    /**
     * @var Config
     */
    private $config;

    public function __construct(Config $config) {
        $this->config = $config;
    }

    /**
     * @param BasicRequestInterface $request
     *
     * @return Command[]
     * @throws \Monyxie\Webhooked\Config\ConfigKeyNotFoundException
     * @throws InvalidSecretException
     * @throws UnsupportedEventException
     */
    public function map(BasicRequestInterface $request) {
        if (! $request->validateSecret($this->config->get('password'))) {
            throw new InvalidSecretException();
        }

        if ($request->getEventName() !== 'push') {
            throw new UnsupportedEventException();
        }

        return $this->mapRepoName($request->getRepositoryFullName());
    }

    /**
     * @param $repoName
     *
     * @return Command[]
     * @throws \Monyxie\Webhooked\Config\ConfigKeyNotFoundException
     */
    private function mapRepoName($repoName) {
        $repos = $this->config->get('repos');

        $commands = [];
        foreach ($repos as $repo) {
            if ($repoName !== $repo['name']) {
                continue;
            }

            $directories = is_array($repo['directories']) ? $repo['directories'] : [$repo['directories']];
            foreach ($directories as $directory) {
                foreach ($repo['commands'] as $command) {
                    $commands []= new Command($command, $directory);
                }
            }
        }

        return $commands;
    }

}