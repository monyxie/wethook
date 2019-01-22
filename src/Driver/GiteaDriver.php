<?php

namespace Monyxie\Webhooked\Driver;

use Monyxie\Webhooked\Driver\Exception\HandlerException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class GiteaDriver implements DriverInterface
{
    /**
     * @var string
     */
    private $secret;

    /**
     * GiteaDriver constructor.
     * @param $secret
     */
    public function __construct($secret)
    {
        $this->secret = $secret;
    }

    /**
     * @inheritdoc
     */
    public function getIdentifier(): string
    {
        return 'gitea';
    }

    /**
     * @return array
     */
    public function getEvents(): array {
        return [
            'push' => 'When commits are pushed to the repository.'
        ];
    }

    /**
     * @inheritdoc
     */
    public function handle(ServerRequestInterface $request, ResponseInterface $response): Result
    {

        $bodyData = json_decode($request->getBody());

        $eventValues = $request->getHeader('X-Gitea-Event');
        $eventName = $eventValues ? end($eventValues) : null;

        if (
            $bodyData === null
            || !isset($bodyData->secret)
            || !isset($bodyData->repository->full_name)
            || empty($eventName)
        ) {
            throw new HandlerException();
        }

        if ($this->secret && $this->secret !== $bodyData->secret) {
            throw new HandlerException();
        }

        $event = new HookEvent();
        $event->driver = $this->getIdentifier();
        $event->event = $event;
        $event->target = $bodyData->repository->full_name;
        $event->data = $bodyData;

        return new Result($response, $event);
    }
}