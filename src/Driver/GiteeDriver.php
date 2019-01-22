<?php

namespace Monyxie\Webhooked\Driver;

use Monyxie\Webhooked\Driver\Exception\HandlerException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class GiteeDriver implements DriverInterface
{
    /**
     * @var string
     */
    private $password = '';

    /**
     * GiteeDriver constructor.
     * @param $password
     */
    public function __construct($password)
    {
        $this->password = $password;
    }

    /**
     * @inheritdoc
     */
    public function getIdentifier(): string
    {
        return 'gitee';
    }

    /**
     * @return array
     */
    public function getEvents(): array {
        return [
            'push_hooks' => 'When commits are pushed to the repository.'
        ];
    }

    /**
     * @inheritdoc
     */
    public function handle(ServerRequestInterface $request, ResponseInterface $response): Result
    {

        $bodyData = json_decode($request->getBody());

        if ($bodyData === null || !isset($bodyData->password) || !isset($bodyData->project->path_with_namespace) || !isset($bodyData->hook_name)) {
            throw new HandlerException();
        }

        if ($this->password && $bodyData->password !== $this->password) {
            throw new HandlerException();
        }

        $event = new HookEvent();
        $event->driver = $this->getIdentifier();
        $event->event = $bodyData->hook_name;
        $event->target = $bodyData->project->path_with_namespace;
        $event->data = $bodyData;

        return new Result($response, $event);
    }
}