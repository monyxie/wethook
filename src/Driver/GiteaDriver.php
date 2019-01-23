<?php

namespace Monyxie\Wethook\Driver;

use Monyxie\Wethook\Driver\Exception\DriverException;
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
            throw new DriverException('Malformed request.');
        }

        if ($this->secret && $this->secret !== $bodyData->secret) {
            throw new DriverException('Secret mismatch.');
        }

        $event = new HookEvent();
        $event->driver = $this->getIdentifier();
        $event->event = $eventName;
        $event->target = $bodyData->repository->full_name;
        $event->data = $bodyData;

        return new Result($response, $event);
    }
}