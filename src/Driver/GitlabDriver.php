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
     * GiteaDriver constructor.
     * @param $token
     */
    public function __construct($token)
    {
        $this->token = $token;
    }

    /**
     * @inheritdoc
     */
    public function getIdentifier(): string
    {
        return 'gitlab';
    }

    /**
     * @return array
     */
    public function getEvents(): array {
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
            $this->getIdentifier(),
            $eventName,
            $bodyData['project']['web_url'],
            $bodyData
        );

        return new Result($response, $event);
    }

    private function validateSignature($signature, $secret, $body) {
        $segments = explode('=', $signature);
        if (! isset($segments[1])) {
            return false;
        }

        $expected = hash_hmac('sha1', $body, $secret);
        return $segments[1] === $expected;
    }
}