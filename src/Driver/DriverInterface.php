<?php

namespace Monyxie\Webhooked\Driver;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface DriverInterface
{
    public function getIdentifier(): string;

    public function getEvents(): array;

    public function handle(ServerRequestInterface $request, ResponseInterface $response): Result;
}