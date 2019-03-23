<?php

namespace Monyxie\Wethook\Driver;

use Monyxie\Wethook\Driver\Exception\DriverException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class GiteaDriver implements DriverInterface
{
    private $endpoint;
    /**
     * @var string
     */
    private $secret;

    /**
     * GiteaDriver constructor.
     * @param string $endpoint
     * @param array $config
     */
    public function __construct(string $endpoint, array $config)
    {
        $this->secret = isset($config['password']) ? $config['password'] : null;
        $this->endpoint = $endpoint;
    }

    /**
     * @return array
     */
    public function getEvents(): array
    {
        return [
            'push' => 'When commits are pushed to the repository.'
        ];
    }

    /**
     * @inheritdoc
     */
    public function handle(ServerRequestInterface $request, ResponseInterface $response): Result
    {

        $bodyData = json_decode($request->getBody(), true);

        $eventValues = $request->getHeader('X-Gitea-Event');
        $eventName = $eventValues ? end($eventValues) : null;

        if (
            $bodyData === null
            || !isset($bodyData['secret'])
            || !isset($bodyData['repository']['html_url'])
            || empty($eventName)
        ) {
            throw new DriverException('Malformed request.');
        }

        if ($this->secret && $this->secret !== $bodyData['secret']) {
            throw new DriverException('Secret mismatch.');
        }

        $event = new Event(
            $this->getEndpoint(),
            $this->getIdentifier(),
            $eventName,
            $bodyData['repository']['html_url'],
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
        return 'gitea';
    }
}