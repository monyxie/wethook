<?php

namespace Monyxie\Wethook\Driver;

use Monyxie\Wethook\Driver\Exception\DriverException;
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
        $bodyData = json_decode($request->getBody(), true);

        if ($bodyData === null || !isset($bodyData['password']) || !isset($bodyData['project']['url']) || !isset($bodyData['hook_name'])) {
            throw new DriverException('Malformed request.');
        }

        if ($this->password && $bodyData['password'] !== $this->password) {
            throw new DriverException('Password mismatch.');
        }

        $event = new Event(
            $this->getIdentifier(),
            $bodyData['hook_name'],
            $bodyData['project']['url'],
            $bodyData
        );

        return new Result($response, $event);
    }
}