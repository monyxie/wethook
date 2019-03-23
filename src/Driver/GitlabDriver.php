<?php

namespace Monyxie\Wethook\Driver;

use Monyxie\Wethook\Driver\Exception\DriverException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class GitlabDriver implements DriverInterface
{
    /**
     * @var string
     */
    private $token;
    /**
     * @var string
     */
    private $endpoint;

    /**
     * GiteaDriver constructor.
     * @param string $endpoint
     * @param array $config
     */
    public function __construct(string $endpoint, array $config)
    {
        $this->token = isset($config['password']) ? $config['password'] : null;
        $this->endpoint = $endpoint;
    }

    /**
     * @return array
     */
    public function getEvents(): array
    {
        return [
            'Push Hook' => 'When commits are pushed to the repository.'
        ];
    }

    /**
     * @inheritdoc
     */
    public function handle(ServerRequestInterface $request, ResponseInterface $response): Result
    {
        $eventValues = $request->getHeader('X-Gitlab-Event');
        $eventName = $eventValues ? end($eventValues) : null;

        $tokenValues = $request->getHeader('X-Gitlab-Token');
        $token = $tokenValues ? end($tokenValues) : null;

        if ($this->token && $this->token !== $token) {
            throw new DriverException('Token mismatch.');
        }

        $requestBody = $request->getBody()->getContents();
        $bodyData = json_decode($requestBody, true);

        if (
            $bodyData === null
            || !isset($bodyData['project']['web_url'])
            || empty($eventName)
        ) {
            throw new DriverException('Malformed request.');
        }

        $event = new Event(
            $this->getEndpoint(),
            $this->getIdentifier(),
            $eventName,
            $bodyData['project']['web_url'],
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
        return 'gitlab';
    }
}