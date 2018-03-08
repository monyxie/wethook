<?php

namespace Monyxie\Webhooked\Request\Gitea;

use Monyxie\Webhooked\Request\MalformedRequestException;
use Monyxie\Webhooked\Request\BasicRequestInterface;
use Psr\Http\Message\ServerRequestInterface;

class GiteaRequest implements BasicRequestInterface {

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

    public function __construct(ServerRequestInterface $request) {

        $bodyData = json_decode($request->getBody());

        $eventValues = $request->getHeader('X-Gitea-Event');
        $event = $eventValues ? end($eventValues) : null;

        if (
            $bodyData === null
            || ! isset($bodyData->secret)
            || ! isset($bodyData->repository->full_name)
            || empty($event)
        ) {
            throw new MalformedRequestException();
        }

        $this->secret = $bodyData->secret;
        $this->repositoryFullName = $bodyData->repository->full_name;
        $this->event = $event;
    }

    public function validateSecret(string $secret): bool {
        return $secret === $this->secret;
    }

    public function getRepositoryFullName(): string {
        return $this->repositoryFullName;
    }

    public function getEventName(): string
    {
        return $this->eventName;
    }
}