<?php

namespace Monyxie\Wethook\Driver;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface DriverInterface
{
    public function __construct(string $endpoint, array $config);

    public function getEndpoint(): string;

    public function getIdentifier(): string;

    public function getEvents(): array;

    public function handle(ServerRequestInterface $request, ResponseInterface $response): Result;
}