<?php

namespace Monyxie\Webhooked\Request\Gitee;

use Monyxie\Webhooked\Request\MalformedRequestException;
use Monyxie\Webhooked\Request\BasicRequestInterface;
use Psr\Http\Message\ServerRequestInterface;

class GiteeRequest implements BasicRequestInterface
{

    /**
     * @var string
     */
    private $secret;

    /**
     * @var string
     */
    private $repositoryFullName;

    /**
     * @var string
     */
    private $eventName;

    /**
     * GiteeRequest constructor.
     * @param ServerRequestInterface $request
     * @throws MalformedRequestException
     */
    public function __construct(ServerRequestInterface $request)
    {

        $bodyData = json_decode($request->getBody());

        if ($bodyData === null || !isset($bodyData->password) || !isset($bodyData->project->path_with_namespace) || !isset($bodyData->hook_name)) {
            throw new MalformedRequestException();
        }

        $this->secret = $bodyData->password;
        $this->repositoryFullName = $bodyData->project->path_with_namespace;

        // only deal with push hooks for now
        $this->eventName = $bodyData->hook_name === 'push_hooks' ? 'push' : $bodyData->hook_name;
    }

    public function validateSecret(string $secret): bool
    {
        return $secret === $this->secret;
    }

    public function getRepositoryFullName(): string
    {
        return $this->repositoryFullName;
    }

    public function getEventName(): string
    {
        return $this->eventName;
    }
}