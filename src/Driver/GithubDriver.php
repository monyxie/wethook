<?php

namespace Monyxie\Wethook\Driver;

use Monyxie\Wethook\Driver\Exception\DriverException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class GithubDriver implements DriverInterface
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
        return 'github';
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
        $eventValues = $request->getHeader('X-GitHub-Event');
        $eventName = $eventValues ? end($eventValues) : null;

        $signatureValues = $request->getHeader('X-Hub-Signature');
        $signature = $signatureValues ? end($signatureValues) : null;

        $requestBody = $request->getBody()->getContents();
        if ($this->secret && !$this->validateSignature($signature, $this->secret, $requestBody)) {
            throw new DriverException('Secret mismatch.');
        }

        $bodyData = json_decode($requestBody, true);

        if (
            $bodyData === null
            || !isset($bodyData['repository']['html_url'])
            || empty($eventName)
        ) {
            throw new DriverException('Malformed request.');
        }

        $event = new Event(
            $this->getIdentifier(),
            $eventName,
            $bodyData['repository']['html_url'],
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