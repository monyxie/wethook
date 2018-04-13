<?php

namespace Monyxie\Webhooked\Server\Task;

use Monyxie\Webhooked\Config\Config;
use Monyxie\Webhooked\Request\BasicRequestInterface;
use Monyxie\Webhooked\Server\Task;

/**
 * Map request to tasks according to configuration.
 * @package Monyxie\Webhooked\Server
 */
class RequestTasksMapper
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
     * @return Task[]
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
     * @return Task[]
     * @throws \Monyxie\Webhooked\Config\ConfigKeyNotFoundException
     */
    private function mapRepoName($repoName) {
        $repos = $this->config->get('repos');

        $tasks = [];
        foreach ($repos as $repo) {
            if ($repoName !== $repo['name']) {
                continue;
            }

            $directories = is_array($repo['directories']) ? $repo['directories'] : [$repo['directories']];
            foreach ($directories as $directory) {
                foreach ($repo['commands'] as $command) {
                    $tasks []= new Task($command, $directory);
                }
            }
        }

        return $tasks;
    }

}