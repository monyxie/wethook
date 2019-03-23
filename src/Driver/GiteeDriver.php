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
     * @var string
     */
    private $endpoint;

    /**
     * GiteeDriver constructor.
     * @param string $endpoint
     * @param array $config
     */
    public function __construct(string $endpoint, array $config)
    {
        $this->password = isset($config['password']) ? $config['password'] : null;
        $this->endpoint = $endpoint;
    }

    /**
     * @return array
     */
    public function getEvents(): array
    {
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
            $this->getEndpoint(),
            $this->getIdentifier(),
            $bodyData['hook_name'],
            $bodyData['project']['url'],
            $bodyData
        );

        return new Result($response, $event);
    }

    /**
     * @return string
     */
    public function getEndpoint(): string
    {
        return $this->endpoint;
    }

    /**
     * @inheritdoc
     */
    public function getIdentifier(): string
    {
        return 'gitee';
    }
}